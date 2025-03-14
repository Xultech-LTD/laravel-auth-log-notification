<?php

use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\SuspicionDetector;

beforeEach(function () {
    // Set default suspicion rules
    config()->set('authlog.suspicion_rules', [
        'new_device' => true,
        'new_location' => true,
    ]);
});

it('detects suspicious login by new device', function () {
    $log = new AuthLog(['is_new_device' => true, 'is_new_location' => false]);

    expect(SuspicionDetector::isSuspicious($log))->toBeTrue();
});

it('detects suspicious login by new location', function () {
    $log = new AuthLog(['is_new_device' => false, 'is_new_location' => true]);

    expect(SuspicionDetector::isSuspicious($log))->toBeTrue();
});

it('detects suspicious login by both device and location', function () {
    $log = new AuthLog(['is_new_device' => true, 'is_new_location' => true]);

    expect(SuspicionDetector::isSuspicious($log))->toBeTrue();
});

it('returns false if not a new device or location', function () {
    $log = new AuthLog(['is_new_device' => false, 'is_new_location' => false]);

    expect(SuspicionDetector::isSuspicious($log))->toBeFalse();
});

it('returns false if detection rules are disabled', function () {
    config()->set('authlog.suspicion_rules', [
        'new_device' => false,
        'new_location' => false,
    ]);

    $log = new AuthLog(['is_new_device' => true, 'is_new_location' => true]);

    expect(SuspicionDetector::isSuspicious($log))->toBeFalse();
});
