<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Spatie\NotificationLog\Models\Concerns\HasNotificationLogItemFunctionality;

class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected NotificationType $type,
        protected array $payload = [],
        protected array $channels = ['database', 'mail'],
    ) {}

    public function via(object $notifiable): array
    {
        // Si l'utilisateur a des préférences de canal
        if (method_exists($notifiable, 'notificationChannels')) {
            return $notifiable->notificationChannels($this->type);
        }

        // Ne pas envoyer de mail pour les notifications info de routine
        if ($this->type->severity() === 'info' && !($this->payload['force_mail'] ?? false)) {
            return ['database'];
        }

        return $this->channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => $this->type->value,
            'label'       => $this->type->label(),
            'severity'    => $this->type->severity(),
            'icon'        => $this->type->icon(),
            'title'       => $this->payload['title'] ?? $this->type->label(),
            'body'        => $this->payload['body'] ?? '',
            'action_url'  => $this->payload['action_url'] ?? null,
            'action_label'=> $this->payload['action_label'] ?? null,
            'entity_type' => $this->payload['entity_type'] ?? null,
            'entity_id'   => $this->payload['entity_id'] ?? null,
            'meta'        => $this->payload['meta'] ?? [],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severity = $this->type->severity();
        $mail = (new MailMessage)
            ->subject($this->payload['title'] ?? $this->type->label())
            ->greeting($this->getGreeting($notifiable))
            ->line($this->payload['body'] ?? $this->type->label());

        if ($severity === 'critical') {
            $mail->error();
        }

        if (!empty($this->payload['action_url'])) {
            $mail->action(
                $this->payload['action_label'] ?? 'Voir les détails',
                $this->payload['action_url']
            );
        }

        if (!empty($this->payload['details']) && is_array($this->payload['details'])) {
            foreach ($this->payload['details'] as $key => $value) {
                $mail->line("**{$key}:** {$value}");
            }
        }

        $mail->line('— GES-CARS Système de notification');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    protected function getGreeting(object $notifiable): string
    {
        $name = $notifiable->full_name ?? $notifiable->first_name ?? 'Utilisateur';
        return "Bonjour {$name},";
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}

