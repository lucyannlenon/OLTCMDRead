<?php

declare(strict_types=1);

use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\MacAddressTableStringParser as DatacomParser;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\MacAddressTableStringParser as ZteParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('', ['config:', 'id::', 'model::']);
$directory = isset($options['config']) ? (string) $options['config'] : '';
if ($directory === '') {
    fwrite(STDERR, "Missing required --config option.\n");
    exit(2);
}

$id = isset($options['id']) && $options['id'] !== false ? (int) $options['id'] : null;
$model = isset($options['model']) && $options['model'] !== false
    ? strtoupper((string) $options['model'])
    : null;

foreach ((new OltInventoryLoader())->loadDirectory($directory) as $entry) {
    $entryModel = strtoupper((string) $entry->olt->model);
    if (($id !== null && $entry->id !== $id)
        || ($model !== null && $entryModel !== $model)
        || !in_array($entryModel, [OltModel::DATACOM, OltModel::ZTE], true)) {
        continue;
    }

    $connection = null;
    try {
        if ($entryModel === OltModel::DATACOM) {
            $connection = new DATACOMConnection($entry->olt);
            $output = $connection->exec('show mac-address-table');
            $count = is_string($output) ? count((new DatacomParser())->parse($output)) : 0;
        } else {
            $connection = new ZTEConnection($entry->olt);
            $output = $connection->exec('show mac');
            $count = is_string($output) ? count((new ZteParser())->parse($output)) : 0;
        }

        printf(
            "#%d %s model=%s profile=%s firmware=%s learnedMacRows=%d\n",
            $entry->id,
            $entry->name,
            $entryModel,
            $entry->olt->cliProfile,
            $entry->olt->firmwareVersion,
            $count
        );
    } catch (Throwable $exception) {
        printf(
            "#%d %s model=%s error=%s\n",
            $entry->id,
            $entry->name,
            $entryModel,
            $exception::class
        );
    } finally {
        if (is_object($connection) && method_exists($connection, 'disconnect')) {
            $connection->disconnect();
        }
    }
}
