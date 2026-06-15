<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Diagnostics\OltVersionEvidenceProbe;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('', ['config:', 'id::', 'model::', 'command::']);
$directory = isset($options['config']) ? (string) $options['config'] : '';
if ($directory === '') {
    fwrite(STDERR, "Missing required --config option.\n");
    exit(2);
}

$id = isset($options['id']) && $options['id'] !== false ? (int) $options['id'] : null;
$model = isset($options['model']) && $options['model'] !== false
    ? strtoupper((string) $options['model'])
    : null;
$command = isset($options['command']) && $options['command'] !== false
    ? (string) $options['command']
    : 'show version';
$probe = new OltVersionEvidenceProbe();

foreach ((new OltInventoryLoader())->loadDirectory($directory) as $entry) {
    if (($id !== null && $entry->id !== $id)
        || ($model !== null && strtoupper((string) $entry->olt->model) !== $model)) {
        continue;
    }

    $result = $probe->probe($entry, $command);
    printf("#%d %s model=%s\n", $result['id'], $result['name'], $result['model']);
    foreach ($result['evidence'] as $line) {
        echo "  {$line}\n";
    }
    if ($result['errorCode'] !== null) {
        echo "  error={$result['errorCode']}\n";
    }
}
