<?php

namespace Xultech\AuthLogNotification\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Notification triggered when session fingerprint mismatch occurs.
 */
class SessionHijackDetected extends Notification
{
    public function __construct(
        public ?string $ip = null,
        public ?string $userAgent = null,
        public ?string $location = null,
        public ?string $route = null
    ) {}

    /**
     * Determine the channels through which to send the notification.
     */
    public function via($notifiable): array
    {
        $channels = ['mail'];

        if (Config::get('authlog.session_fingerprint.notify_admins.slack_webhooks')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Build the mail representation.
     */
    public function toMail($notifiable): MailMessage
    {
        $name = is_object($notifiable) ? ($notifiable->name ?? 'User') : 'Admin';

        return (new MailMessage)
            ->subject('⚠️ Session Integrity Warning')
            ->greeting("Hello {$name},")
            ->line('We detected a possible session hijack.')
            ->line('IP: ' . ($this->ip ?? 'Unknown'))
            ->line('Location: ' . ($this->location ?? 'Unknown'))
            ->line('User Agent: ' . ($this->userAgent ?? 'Unknown'))
            ->line('Route Accessed: ' . ($this->route ?? 'Unknown'))
            ->line('Time: ' . Carbon::now()->toDateTimeString())
            ->line('The session has been invalidated as a precaution.');
    }

    /**
     * Build the Slack representation.
     */
    public function toSlack($notifiable): ?SlackMessage
    {
        if (!class_exists(\Illuminate\Notifications\Messages\SlackMessage::class)) {
            return null;
        }

        return (new SlackMessage)
            ->error()
            ->content('🚨 Possible Session Hijack Detected')
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'IP' => $this->ip ?? 'Unknown',
                    'User Agent' => $this->userAgent ?? 'Unknown',
                    'Location' => $this->location ?? 'Unknown',
                    'Route' => $this->route ?? 'N/A',
                    'Time' => now()->toDateTimeString(),
                ]);
            });
    }
}
