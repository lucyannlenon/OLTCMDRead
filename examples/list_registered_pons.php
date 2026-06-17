<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Diagnostics\FiberhomeTl1Config;
use LLENON\OltInformation\Diagnostics\OltInventoryEntry;
use LLENON\OltInformation\Diagnostics\OltInventoryLoader;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\CDATA\Command\ListPonsCommand as CDataListPonsCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListPonsCommand as DatacomListPonsCommand;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListPonsCommand as FiberhomeListPonsCommand;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListPonsCommand as VSolEponListPonsCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnection;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListPonsCommand as VSolGponListPonsCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnection;
use LLENON\OltInformation\OLT\ZTE\Command\ListPonsCommand as ZteListPonsCommand;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('', ['config:', 'id::', 'model::', 'summary::', 'help']);
if (isset($options['help'])) {
    echo <<<'TEXT'
Usage:
  php examples/list_registered_pons.php --config=examples/config/olts
      [--id=6] [--model=DATACOM] [--summary=/tmp/olt-pons-summary.json]

Lists unique PONs that currently have registered ONUs. The script continues
when one OLT fails and marks the error in the output.

Fiberhome TL1 requires IPSERVER_TL1, USERNAME_TL1, and PASSWORD_TL1 when the
inventory entry does not provide a TL1 gateway address.
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
$model = isset($options['model']) && $options['model'] !== false ? strtoupper((string) $options['model']) : null;

try {
    $entries = (new OltInventoryLoader())->loadDirectory($directory);
    $summary = [];

    foreach ($entries as $entry) {
        if ($id !== null && $entry->id !== $id) {
            continue;
        }

        if ($model !== null && strtoupper((string) $entry->olt->model) !== $model) {
            continue;
        }

        $startedAt = hrtime(true);
        try {
            $pons = listRegisteredPons($entry);
            $summary[] = [
                'id' => $entry->id,
                'name' => $entry->name,
                'model' => strtoupper((string) $entry->olt->model),
                'status' => 'ok',
                'pons' => $pons,
                'ponCount' => count($pons),
                'durationMs' => durationMs($startedAt),
                'error' => null,
            ];
        } catch (Throwable $exception) {
            $summary[] = [
                'id' => $entry->id,
                'name' => $entry->name,
                'model' => strtoupper((string) $entry->olt->model),
                'status' => 'error',
                'pons' => [],
                'ponCount' => 0,
                'durationMs' => durationMs($startedAt),
                'error' => $exception->getMessage(),
            ];
        }
    }

    foreach ($summary as $item) {
        printf(
            "#%d %s model=%s status=%s pons=%s duration=%dms error=%s\n",
            $item['id'],
            $item['name'],
            $item['model'],
            $item['status'],
            $item['pons'] === [] ? '-' : implode(',', $item['pons']),
            $item['durationMs'],
            $item['error'] ?? 'none'
        );
    }

    if (isset($options['summary']) && $options['summary'] !== false) {
        $target = (string) $options['summary'];
        if ($target === '' || file_put_contents(
            $target,
            json_encode($summary, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ) === false) {
            throw new RuntimeException('Unable to write PON summary.');
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * @return list<string>
 */
function colistRegisteredPons(OltInventoryEntry $entry): array
{
    $connection = null;

    try {
        return match (strtoupper((string) $entry->olt->model)) {
            OltModel::CDATA => (function () use ($entry, &$connection): array {
                $connection = new CDATAConnection($entry->olt, enforceFirmwareVersion: false);
                return (new CDataListPonsCommand($connection))->execute();
            })(),
            OltModel::DATACOM => (function () use ($entry, &$connection): array {
                $connection = new DATACOMConnection($entry->olt, enforceFirmwareVersion: false);
                return (new DatacomListPonsCommand($connection))->execute();
            })(),
            OltModel::ZTE => (function () use ($entry, &$connection): array {
                $connection = new ZTEConnection($entry->olt, enforceFirmwareVersion: false);
                return (new ZteListPonsCommand($connection))->execute();
            })(),
            OltModel::VSOL => (function () use ($entry, &$connection): array {
                $connection = new VSolEponConnection($entry->olt, enforceFirmwareVersion: false);
                return (new VSolEponListPonsCommand($connection))->execute();
            })(),
            OltModel::VSOLGPON => (function () use ($entry, &$connection): array {
                $connection = new VSolGponConnection($entry->olt, enforceFirmwareVersion: false);
                return (new VSolGponListPonsCommand($connection))->execute();
            })(),
            OltModel::FIBERHOME, OltModel::FIBERHOMEOLDVERSION => (function () use ($entry, &$connection): array {
                $config = fiberhomeTl1Config($entry);
                $connection = new FiberhomeConnection(
                    (string) $entry->olt->ip,
                    $config->gatewayAddress,
                    $config->username,
                    $config->password
                );
                return (new FiberhomeListPonsCommand($connection))->execute();
            })(),
            default => throw new InvalidArgumentException(
                "Unsupported OLT model '{$entry->olt->model}' for PON listing."
            ),
        };
    } finally {
        disconnectConnection($connection);
    }
}

function fiberhomeTl1Config(OltInventoryEntry $entry): FiberhomeTl1Config
{
    $gatewayAddress = $entry->tl1Server ?? getenv('IPSERVER_TL1') ?: '';
    $username = getenv('USERNAME_TL1') ?: '';
    $password = getenv('PASSWORD_TL1') ?: '';

    return new FiberhomeTl1Config($gatewayAddress, $username, $password);
}

function disconnectConnection(mixed $connection): void
{
    if (!is_object($connection)) {
        return;
    }

    if ($connection instanceof ConnectionInterface && method_exists($connection, 'disconnect')) {
        $connection->disconnect();
        return;
    }

    if (method_exists($connection, 'close')) {
        $connection->close();
    }
}

function durationMs(int $startedAt): int
{
    return max(0, (int) ((hrtime(true) - $startedAt) / 1_000_000));
}
