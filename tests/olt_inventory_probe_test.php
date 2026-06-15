<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltDiagnosticResult;
use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Diagnostics\OltInventoryProbe;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$entry = (new OltInventoryLoader())->loadFile(__DIR__ . '/fixtures/inventory/valid.json');
$probe = new OltInventoryProbe(
    diagnostic: static fn ($inventory): OltDiagnosticResult => new OltDiagnosticResult(
        true,
        true,
        (string) $inventory->olt->model,
        (string) $inventory->olt->serviceCommunication,
        'device',
        'V1.0.0',
        null,
        5,
        null,
        'OLT credentials are valid.'
    )
);

$results = $probe->run([$entry], id: 1);
expect(count($results) === 1, 'Selected inventory entry was not probed.');

$safe = $results[0]->toSafeArray();
$encoded = json_encode($safe, JSON_THROW_ON_ERROR);
expect(!str_contains($encoded, 'test-password'), 'Probe result leaked a password.');
expect(!str_contains($encoded, '192.0.2.10'), 'Probe result leaked an address.');
expect($safe['firmwareDetected'] === 'V1.0.0', 'Detected firmware was not preserved.');

expect($probe->run([$entry], id: 2) === [], 'Non-selected inventory entries must be skipped.');

echo "OLT inventory probe tests passed.\n";
