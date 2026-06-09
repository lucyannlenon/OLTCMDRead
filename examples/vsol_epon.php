<?php

use LLENON\OltInformation\Adapters\VSolOLTCmd;
use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\OnuRouterMacDiscovery;

require __DIR__ . '/../vendor/autoload.php';

foreach (['VSOL_EPON_HOST', 'VSOL_EPON_USERNAME', 'VSOL_EPON_PASSWORD', 'VSOL_EPON_ONU_MAC'] as $name) {
    if (getenv($name) === false) {
        throw new RuntimeException("Missing environment variable {$name}.");
    }
}

$olt = new OLT(
    getenv('VSOL_EPON_USERNAME'),
    getenv('VSOL_EPON_PASSWORD'),
    OltModel::VSOL,
    getenv('VSOL_EPON_HOST'),
    getenv('VSOL_EPON_PORT') ?: '23',
    'telnet',
    getenv('VSOL_EPON_NAME') ?: 'VSOL EPON',
    OltCliProfile::VSOL_EPON_CLI_V1,
    getenv('VSOL_EPON_FIRMWARE') ?: 'V1.01.51_230922190137'
);

$client = new Client(
    getenv('VSOL_EPON_CLIENT_LOGIN') ?: 'example',
    getenv('VSOL_EPON_ONU_MAC'),
    null
);

$clientData = (new VSolOLTCmd($olt, $client))->getDadosDoCliente();

echo json_encode([
    'pon' => $clientData->pon,
    'onu_id' => $clientData->onuPosition,
    'status' => $clientData->status,
    'signal' => $clientData->signal ?? null,
    'temperature' => $clientData->onuTemperatura,
    'distance' => $clientData->distance ?? null,
    'uptime' => $clientData->uptime ?? null,
    'ethernet_status' => isset($clientData->ethernet)
        ? $clientData->ethernet->getStatus()
        : null,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;

if (getenv('VSOL_EPON_DISCOVER_MACS') !== '1') {
    return;
}

$connection = new VSolEponConnection($olt);

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
