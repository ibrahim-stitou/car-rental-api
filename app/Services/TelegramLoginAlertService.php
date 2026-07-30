<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Login alerting via Telegram — the office has no fixed IP, so instead of a
 * whitelist we compare each login's IP against that SAME user's last known
 * login IP: unchanged -> assume same place, stay quiet; changed -> notify,
 * since that's the actual signal of interest ("did this account just show up
 * somewhere new"). Comparing against the previous user's IP instead (a global
 * "last seen" instead of per-user) would false-positive on the very first
 * person of the day and miss a second person genuinely logging in from
 * outside — per-user is the same principle real "new device/location" login
 * alerts (Gmail, banks, ...) use.
 */
class TelegramLoginAlertService
{
    public function notifyIfNewLocation(User $user, string $ip): void
    {
        $previousIp = $user->last_login_ip;
        $user->forceFill(['last_login_ip' => $ip])->saveQuietly();

        if ($previousIp === $ip) {
            return;
        }

        $this->send($user, $ip, $previousIp);
    }

    private function send(User $user, string $ip, ?string $previousIp): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) {
            return;
        }

        $location = $this->geolocate($ip);
        $lines = [
            '🔐 *Nouvelle connexion*',
            "Utilisateur : {$user->full_name} ({$user->email})",
            'Date : ' . now()->format('d/m/Y H:i'),
            "IP : `{$ip}`" . ($location ? " — {$location}" : ''),
            $previousIp
                ? "IP précédente : `{$previousIp}`"
                : '_Première connexion enregistrée pour cet utilisateur._',
        ];

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => implode("\n", $lines),
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram login alert failed: ' . $e->getMessage());
        }
    }

    /**
     * Best-effort city/country/ISP lookup — purely informational context for
     * the alert, never allowed to delay or break the login flow itself
     * (caller already runs this after the HTTP response has been sent).
     */
    private function geolocate(string $ip): ?string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        try {
            $data = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,city,isp',
            ])->json();

            if (($data['status'] ?? null) !== 'success') {
                return null;
            }

            return trim(collect([$data['city'] ?? null, $data['country'] ?? null])->filter()->join(', ')
                . (!empty($data['isp']) ? " ({$data['isp']})" : ''));
        } catch (\Throwable $e) {
            return null;
        }
    }
}