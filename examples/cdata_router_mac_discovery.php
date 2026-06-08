<?php

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\CDATA\OnuRouterMacDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$required = ['CDATA_HOST', 'CDATA_USERNAME', 'CDATA_PASSWORD'];
foreach ($required as $name) {
    if (getenv($name) === false) {
        throw new RuntimeException("Missing environment variable {$name}.");
    }
}

$olt = new OLT(
    getenv('CDATA_USERNAME'),
    getenv('CDATA_PASSWORD'),
    OltModel::CDATA,
    getenv('CDATA_HOST'),
    getenv('CDATA_PORT') ?: '22',
    'ssh',
    getenv('CDATA_NAME') ?: 'CDATA EPON'
);

$connection = new CDATAConnection($olt);
$discovery = new OnuRouterMacDiscovery($connection);

try {
    foreach ($discovery->discoverAll() as $item) {
        foreach ($item['mac_addresses'] as $mac) {
            echo implode("\t", [
                $item['onu']->getPon(),
                $item['onu']->getId(),
                $item['onu']->getGponId(),
                $mac->macAddress,
                $mac->vlan,
            ]) . PHP_EOL;
        }
    }
} finally {
    $connection->disconnect();
}
