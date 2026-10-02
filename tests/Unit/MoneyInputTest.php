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

test('money input formats stored minor units without floating point math', function (int $minor, string $expected) {
    expect(MoneyInput::fromMinorUnits($minor))->toBe($expected);
})->with([
    [1, '0.01'],
    [1230, '12.30'],
    [12345, '123.45'],
]);

test('money input converts signed decimal amounts for utility balances', function (string $value, int $expected) {
    expect(MoneyInput::toSignedMinorUnits($value))->toBe($expected);
})->with([
    ['439.38', 43938],
    ['-12.34', -1234],
    ['-0.50', -50],
    ['0', 0],
]);

test('money input formats signed stored minor units without losing subunit signs', function (int $minor, string $expected) {
    expect(MoneyInput::fromSignedMinorUnits($minor))->toBe($expected);
})->with([
    [43938, '439.38'],
    [-1234, '-12.34'],
    [-50, '-0.50'],
    [0, '0.00'],
]);

