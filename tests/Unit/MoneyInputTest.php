<?php

use App\Support\MoneyInput;
use InvalidArgumentException;

test('money input converts decimal amounts to integer minor units', function (string $value, int $expected) {
    expect(MoneyInput::toMinorUnits($value))->toBe($expected);
})->with([
    ['0.01', 1],
    ['12', 1200],
    ['12.3', 1230],
    ['12.34', 1234],
    ['12,34', 1234],
]);

test('money input rejects unsupported formats', function (string $value) {
    expect(fn () => MoneyInput::toMinorUnits($value))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['-1'],
    ['12.345'],
    ['1 234,50'],
    ['abc'],
]);
