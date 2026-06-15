<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltProbeSanitizer;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$sanitizer = new OltProbeSanitizer();
$safe = $sanitizer->sanitize(
    'host=192.0.2.10 mac=AA:BB:CC:DD:EE:FF serial=ZTEG12345678 password=secret',
    ['secret']
);

expect(!str_contains($safe, '192.0.2.10'), 'IP address was not sanitized.');
expect(!str_contains($safe, 'AA:BB:CC:DD:EE:FF'), 'MAC address was not sanitized.');
expect(!str_contains($safe, 'ZTEG12345678'), 'Serial number was not sanitized.');
expect(!str_contains($safe, 'secret'), 'Explicit secret was not sanitized.');

$again = $sanitizer->sanitize('AA:BB:CC:DD:EE:FF');
expect(str_contains($again, '[MAC_1]'), 'Sanitization placeholders must be deterministic.');

echo "OLT probe sanitizer tests passed.\n";
