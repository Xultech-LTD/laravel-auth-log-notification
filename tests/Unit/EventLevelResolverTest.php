<?php

use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Constants\AuthEventLevel;

it('can check if enum is supported', function () {
    expect(EventLevelResolver::enumSupported())->toBeBool();
});

it('returns a valid label for a known value', function () {
    $label = EventLevelResolver::label('login');
    expect($label)->toBeString();
    expect($label)->not()->toBe('');
});

it('falls back to constant label if enum fails', function () {
    $label = EventLevelResolver::label('logout');
    expect($label)->toBe(AuthEventLevel::label('logout'));
});

it('returns false for invalid event level', function () {
    expect(EventLevelResolver::isValid('invalid'))->toBeFalse();
});

it('returns true for valid event level', function () {
    expect(EventLevelResolver::isValid('login'))->toBeTrue();
});

it('returns an array of values', function () {
    expect(EventLevelResolver::values())->toBeArray();
    expect(EventLevelResolver::values())->toContain('login', 'logout', 'failed');
});
