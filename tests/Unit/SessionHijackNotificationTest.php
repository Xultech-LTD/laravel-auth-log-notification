<?php

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Config;
use Xultech\AuthLogNotification\Notifications\SessionHijackDetected;

use function Pest\Faker\faker;

beforeEach(function () {
    Config::set('authlog.session_fingerprint.notify_admins.slack_webhooks', []);
});

it('uses only mail by default', function () {
    $notification = new SessionHijackDetected();

    expect($notification->via(fakeUser()))->toBe(['mail']);
});

it('includes slack when slack_webhooks are configured', function () {
    Config::set('authlog.session_fingerprint.notify_admins.slack_webhooks', ['https://slack.example/webhook']);

    $notification = new SessionHijackDetected();
    expect($notification->via(fakeUser()))->toContain('slack');
});

it('generates a MailMessage with expected content', function () {
    $notification = new SessionHijackDetected(
        ip: '192.168.0.1',
        userAgent: 'Firefox',
        location: 'Lagos, Nigeria',
        route: '/dashboard'
    );

    $mail = $notification->toMail(fakeUser());

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Session Integrity')
        ->and($mail->introLines)->toContain('We detected a possible session hijack.');
});

it('returns SlackMessage when class exists and webhooks configured', function () {
    Config::set('authlog.session_fingerprint.notify_admins.slack_webhooks', ['https://fake.slack']);

    $notification = new SessionHijackDetected(
        ip: '10.0.0.1',
        userAgent: 'Chrome',
        location: 'Enugu, Nigeria',
        route: '/admin/settings'
    );

    $slack = $notification->toSlack(fakeUser());

    expect($slack)->toBeInstanceOf(SlackMessage::class);
});

function fakeUser(): object {
    return new class {
        public string $name = 'Test Admin';
    };
}
