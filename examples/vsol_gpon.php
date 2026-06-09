<?php

use LLENON\OltInformation\Adapters\VSolOLTGPONCmd;
use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnection;
use LLENON\OltInformation\OLT\VSOL\GPON\OnuRouterMacDiscovery;

require __DIR__ . '/../vendor/autoload.php';

foreach (['VSOL_HOST', 'VSOL_USERNAME', 'VSOL_PASSWORD', 'VSOL_ONU_SERIAL'] as $name) {
    if (getenv($name) === false) {
        throw new RuntimeException("Missing environment variable {$name}.");
    }
}

$olt = new OLT(
    getenv('VSOL_USERNAME'),
    getenv('VSOL_PASSWORD'),
    OltModel::VSOLGPON,
    getenv('VSOL_HOST'),
    getenv('VSOL_PORT') ?: '22',
    'ssh',
    getenv('VSOL_NAME') ?: 'VSOL GPON',
    OltCliProfile::VSOL_GPON_CLI_V2,
    getenv('VSOL_FIRMWARE') ?: 'V2.1.8R'
);

$client = new Client(
    getenv('VSOL_CLIENT_LOGIN') ?: 'example',
    null,
    getenv('VSOL_ONU_SERIAL')
);

$clientData = (new VSolOLTGPONCmd($olt, $client))->getDadosDoCliente();

echo json_encode([
    'pon' => $clientData->pon,
    'onu_id' => $clientData->onuPosition,
    'status' => $clientData->status,
    'signal' => $clientData->signal ?? null,
    'temperature' => $clientData->onuTemperatura,
    'distance' => $clientData->distance ?? null,
    'uptime' => $clientData->uptime ?? null,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;

if (getenv('VSOL_DISCOVER_MACS') !== '1') {
    return;
}

$connection = new VSolGponConnection($olt);

try {
    $discovery = new OnuRouterMacDiscovery($connection);

    foreach ($discovery->discoverAll() as $item) {
        foreach ($item['mac_addresses'] as $mac) {
            echo implode("\t", [
                $item['onu']->getPon(),
                $item['onu']->getId(),
                $mac->macAddress,
                $mac->vlan,
            ]) . PHP_EOL;
        }
    }
} finally {
    $connection->disconnect();
}
