<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Diagnostics\OltVersionEvidenceProbe;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$entry = (new OltInventoryLoader())->loadFile(__DIR__ . '/fixtures/inventory/valid.json');
$connection = new class implements ConnectionInterface {
    public bool $disconnected = false;

    public function exec(string $cmd): string
    {
        return "Host 192.0.2.10\nSoftware release: V1.2.3\nMAC AA:BB:CC:DD:EE:FF\n";
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
        $this->disconnected = true;
    }
};

$result = (new OltVersionEvidenceProbe(
    connectionFactory: static fn ($inventory): ConnectionInterface => $connection
))->probe($entry);

expect(
    in_array('Software release: V1.2.3', $result['evidence'], true),
    'Version evidence must be retained.'
);
expect($connection->disconnected, 'Version evidence connection must be disconnected.');
expect(!str_contains(json_encode($result, JSON_THROW_ON_ERROR), 'test-password'), 'Evidence leaked a password.');
expect(!str_contains(json_encode($result, JSON_THROW_ON_ERROR), '192.0.2.10'), 'Evidence leaked an address.');
expect(!str_contains(json_encode($result, JSON_THROW_ON_ERROR), 'AA:BB:CC:DD:EE:FF'), 'Evidence leaked a MAC.');

$fallbackConnection = new class implements ConnectionInterface {
    public function exec(string $cmd): string
    {
        return "% Invalid input detected\n";
    }

    public function setTimeout(int $timeout): void
    {
    }
};
$fallback = (new OltVersionEvidenceProbe(
    connectionFactory: static fn ($inventory): ConnectionInterface => $fallbackConnection
))->probe($entry);
expect($fallback['evidence'] === ['% Invalid input detected'], 'Fallback evidence was not retained.');

try {
    (new OltVersionEvidenceProbe(
        connectionFactory: static fn ($inventory): ConnectionInterface => $fallbackConnection
    ))->probe($entry, 'configure terminal');
    throw new RuntimeException('Mutating evidence commands must be rejected.');
} catch (InvalidArgumentException) {
}

echo "OLT version evidence probe tests passed.\n";
