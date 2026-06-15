<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltInventoryLoader;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$loader = new OltInventoryLoader();
$entry = $loader->loadFile(__DIR__ . '/fixtures/inventory/valid.json');
$safe = $entry->safeMetadata();

expect($entry->id === 1, 'Inventory ID was not loaded.');
expect($entry->olt->model === 'ZTE', 'Inventory model was not loaded.');
expect(!array_key_exists('password', $safe), 'Safe metadata must not contain passwords.');
expect(!array_key_exists('address', $safe), 'Safe metadata must not contain addresses.');

try {
    $loader->loadFile(__DIR__ . '/fixtures/inventory/invalid.json');
    throw new RuntimeException('Invalid inventory must be rejected.');
} catch (InvalidArgumentException $exception) {
    expect(!str_contains($exception->getMessage(), 'test-password'), 'Inventory errors leaked a password.');
}

echo "OLT inventory loader tests passed.\n";
