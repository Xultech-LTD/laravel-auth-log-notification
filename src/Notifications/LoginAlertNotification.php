<?php

namespace Xultech\AuthLogNotification\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Config;
use Xultech\AuthLogNotification\Models\AuthLog;
use Illuminate\Notifications\Messages\VonageMessage;


/**
 * Notification sent when a new login is detected.
 *
 * Supports multiple channels:
 * - Mail (Markdown or HTML)
 * - Slack
 * - SMS via Vonage (but still referred to as 'nexmo' in config for compatibility)
 */
class LoginAlertNotification extends Notification
{
    public function __construct(public AuthLog $log) {}

    /**
     * Determine which channels to send notification through.
     */
    public function via($notifiable): array
    {
        $config = Config::get('authlog.notification.channels_config', []);

        $channels = [];

        // Enable mail if configured
        if (!empty($config['mail']['enabled'])) {
            $channels[] = 'mail';
        }

        // Enable Slack if configured and class exists
        if (!empty($config['slack']['enabled']) && class_exists(SlackMessage::class)) {
            $channels[] = 'slack';
        }

        // Enable Nexmo if configured and class exists
        if (!empty($config['nexmo']['enabled']) && class_exists(VonageMessage::class)) {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    /**
     * Build the mail representation.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = Config::get('authlog.default_notification.subject', 'New Login Detected');
        $view = Config::get('authlog.default_notification.mail_view', 'authlog::mail.login-alert');
        $from = Config::get('authlog.default_notification.mail_from', 'no-reply@example.com');
        $viewType  = config('authlog.default_notification.view_type', 'markdown');

        $mail = (new MailMessage)
            ->from($from)
            ->subject($subject);

        $data = [
            'log' => $this->log,
            'user' => $notifiable,
        ];

        return $viewType === 'markdown'
            ? $mail->markdown($view, $data)
            : $mail->view($view, $data);

    }
    /**
     * Build the Slack representation.
     */
    public function toSlack($notifiable): SlackMessage
    {
        $channel = Config::get('authlog.notification.channels_config.slack.channel')
            ?? Config::get('authlog.default_notification.slack_channel', '#security-alerts');

        return (new SlackMessage)
            ->to($channel)
            ->warning()
            ->content("⚠️ New login detected for {$notifiable->name}")
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Event'    => $this->log->event_type,
                    'IP'       => $this->log->ip_address ?? 'Unknown',
                    'Location' => $this->log->formatted_location,
                    'Device'   => $this->log->device_summary,
                    'Time'     => $this->log->login_at_formatted,
                ]);
            });
    }
    /**
     * Build the Nexmo (SMS) representation.
     */
    public function toNexmo($notifiable)
    {
        $location = $this->log->formatted_location;
        $ip = $this->log->ip_address ?? 'Unknown';
        $timestamp = $this->log->login_at_formatted;

        return (new \Illuminate\Notifications\Messages\VonageMessage)
            ->content("New login alert!\nIP: {$ip}\nLocation: {$location}\nTime: {$timestamp}");
    }
}