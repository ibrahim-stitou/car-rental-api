<?php

namespace App\Modules\Setting\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    private const CACHE_KEY = 'app_settings';

    public function getAll(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn() => Setting::getAllGrouped());
    }

    public function getGroup(string $group): array
    {
        $all = $this->getAll();
        return $all[$group] ?? [];
    }

    public function updateGroup(string $group, array $data): array
    {
        Setting::updateGroup($group, $data);
        Cache::forget(self::CACHE_KEY);
        return $this->getGroup($group);
    }

    public function uploadLogo(UploadedFile $file): string
    {
        $path = $file->storeAs('company', 'logo.' . $file->extension(), 'public');
        $url  = Storage::disk('public')->url($path);
        Setting::updateGroup('company', ['logo_url' => $url]);
        Cache::forget(self::CACHE_KEY);
        return $url;
    }

    public function uploadHeroImage(UploadedFile $file): string
    {
        $path = $file->storeAs('website', 'hero.' . $file->extension(), 'public');
        $url  = Storage::disk('public')->url($path);
        Setting::updateGroup('website', ['hero_image_url' => $url]);
        Cache::forget(self::CACHE_KEY);
        return $url;
    }

    public function getPublicSettings(): array
    {
        $all = $this->getAll();
        return [
            'company' => collect($all['company'] ?? [])->only([
                'name', 'tagline', 'address', 'city', 'country',
                'phone', 'phone2', 'email', 'logo_url',
                'facebook_url', 'instagram_url', 'twitter_url', 'whatsapp_number',
            ])->toArray(),
            'website' => $all['website'] ?? [],
        ];
    }
}
