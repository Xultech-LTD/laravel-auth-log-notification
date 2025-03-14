<?php

use Illuminate\Http\Request;
use Xultech\AuthLogNotification\Services\SessionFingerprintService;

it('generates consistent fingerprint for identical request', function () {
    $request = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_ACCEPT_LANGUAGE' => 'en-US',
    ]);

    $fp1 = SessionFingerprintService::generate($request);
    $fp2 = SessionFingerprintService::generate($request);

    expect($fp1)->toBe($fp2);
});

it('generates different fingerprints for different requests', function () {
    $req1 = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_ACCEPT_LANGUAGE' => 'en-US',
    ]);

    $req2 = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.10',
        'HTTP_USER_AGENT' => 'CustomBot/2.0',
        'HTTP_ACCEPT_LANGUAGE' => 'fr-FR',
    ]);

    $fp1 = SessionFingerprintService::generate($req1);
    $fp2 = SessionFingerprintService::generate($req2);

    expect($fp1)->not->toBe($fp2);
});

it('matches returns true for same request and stored fingerprint', function () {
    $request = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_USER_AGENT' => 'Firefox',
        'HTTP_ACCEPT_LANGUAGE' => 'en-GB',
    ]);

    $stored = SessionFingerprintService::generate($request);

    expect(SessionFingerprintService::matches($stored, $request))->toBeTrue();
});

it('matches returns false for different request data', function () {
    $req1 = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_USER_AGENT' => 'Firefox',
        'HTTP_ACCEPT_LANGUAGE' => 'en-GB',
    ]);

    $req2 = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.2',
        'HTTP_USER_AGENT' => 'Safari',
        'HTTP_ACCEPT_LANGUAGE' => 'fr-FR',
    ]);

    $stored = SessionFingerprintService::generate($req1);

    expect(SessionFingerprintService::matches($stored, $req2))->toBeFalse();
});
