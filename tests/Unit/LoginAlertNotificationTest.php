<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Notifications\LoginAlertNotification;
use Tests\Stubs\UserStub;

beforeEach(function () {
    // Basic log stub
    $this->log = new AuthLog([
        'ip_address' => '192.168.1.1',
        'country' => 'Nigeria',
        'city' => 'Enugu',
        'location' => 'Nigeria, Enugu',
        'device' => 'Mobile',
        'platform' => 'Android',
        'browser' => 'Chrome',
        'login_at' => \Carbon\Carbon::now(),
        'event_level' => 'login'
    ]);

    // Dummy user
    $this->user = new UserStub(['name' => 'John Doe']);
});

it('returns correct channels based on config', function () {
    Config::set('authlog.notification.channels_config', [
        'mail' => ['enabled' => true],
        'slack' => ['enabled' => true],
        'nexmo' => ['enabled' => true],
    ]);

    $notification = new LoginAlertNotification($this->log);
    $channels = $notification->via($this->user);

    expect($channels)->toContain('mail', 'slack', 'vonage');
});

it('returns a properly formatted MailMessage', function () {
    Config::set('authlog.default_notification.subject', 'New Login Alert');
    Config::set('authlog.default_notification.mail_view', 'authlog::mail.login-alert');
    Config::set('authlog.default_notification.mail_from', 'security@example.com');

    $notification = new LoginAlertNotification($this->log);
    $mail = $notification->toMail($this->user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('New Login Alert');
});

it('returns a SlackMessage if enabled', function () {
    Config::set('authlog.notification.channels_config.slack.channel', '#test-channel');
    Config::set('authlog.notification.channels_config.slack.enabled', true);

    $notification = new LoginAlertNotification($this->log);
    $slack = $notification->toSlack($this->user);

    expect($slack)->toBeInstanceOf(SlackMessage::class);
});

it('returns a VonageMessage if enabled', function () {
    Config::set('authlog.notification.channels_config.nexmo.enabled', true);

    $notification = new LoginAlertNotification($this->log);
    $sms = $notification->toNexmo($this->user);

    expect($sms)->toBeInstanceOf(VonageMessage::class)
        ->and($sms->content)->toContain('New login alert!')
        ->and($sms->content)->toContain('192.168.1.1');
});
