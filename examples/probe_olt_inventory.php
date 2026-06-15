<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Diagnostics\OltInventoryProbe;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('', ['config:', 'id::', 'model::', 'summary::', 'help']);
if (isset($options['help'])) {
    echo <<<'TEXT'
Usage:
  php examples/probe_olt_inventory.php --config=examples/config/olts
      [--id=6] [--model=DATACOM] [--summary=/tmp/olt-probe-summary.json]

Runs connection and firmware diagnostics only. Output never includes
credentials, device addresses, or raw command responses.

Fiberhome TL1 uses IPSERVER_TL1, USERNAME_TL1, and PASSWORD_TL1 from the
environment. Per-device credentials are not used for the shared gateway.
TEXT;
    echo PHP_EOL;
    exit(0);
}

$directory = isset($options['config']) ? (string) $options['config'] : '';
if ($directory === '') {
    fwrite(STDERR, "Missing required --config option.\n");
    exit(2);
}

$id = isset($options['id']) && $options['id'] !== false ? (int) $options['id'] : null;
$model = isset($options['model']) && $options['model'] !== false ? (string) $options['model'] : null;

try {
    $entries = (new OltInventoryLoader())->loadDirectory($directory);
    $results = (new OltInventoryProbe())->run($entries, $id, $model);
    $summary = array_map(static fn ($result): array => $result->toSafeArray(), $results);

    foreach ($summary as $item) {
        printf(
            "#%d %s model=%s transport=%s reachable=%s credentials=%s firmware=%s match=%s duration=%dms error=%s\n",
            $item['id'],
            $item['name'],
            $item['model'],
            $item['transport'],
            $item['reachable'] ? 'yes' : 'no',
            $item['credentialsValid'] ? 'valid' : 'invalid',
            $item['firmwareDetected'] ?? 'unavailable',
            $item['firmwareMatch'] === null ? 'unknown' : ($item['firmwareMatch'] ? 'yes' : 'no'),
            $item['durationMs'],
            $item['errorCode'] ?? 'none'
        );
    }

    if (isset($options['summary']) && $options['summary'] !== false) {
        $target = (string) $options['summary'];
        if ($target === '' || file_put_contents(
            $target,
            json_encode($summary, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ) === false) {
            throw new RuntimeException('Unable to write probe summary.');
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
