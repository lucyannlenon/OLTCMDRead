<?php

declare(strict_types=1);

use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\Dto\OnuEthernetStatus;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Dto\OnuOperationalStatus;
use LLENON\OltInformation\OLT\Dto\OnuOpticalMetrics;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$identity = new OnuIdentity('ZTE', '1/1/1', '2', 'ZTEG00000001', 'working');
expect($identity->onuId === '2', 'ONU identity was not preserved.');

$status = new OnuOperationalStatus('working', 1234, 3600);
expect($status->isOnline(), 'Working state must be considered online.');

$optical = new OnuOpticalMetrics(-18.25, 2.1, 42.5, 3.3, 12.4);
expect($optical->toArray()['temperatureCelsius'] === 42.5, 'Temperature was not preserved.');

$ethernet = new OnuEthernetStatus('up', 1000, 'auto', 'disabled');
expect($ethernet->speedMbps === 1000, 'Ethernet speed was not preserved.');

$location = new MacLocation('1122.3344.5566', '1/1/1', '2', '100', 'dynamic', 'eth1');
expect($location->macAddress === '11:22:33:44:55:66', 'MAC address was not normalized.');

try {
    new OnuOperationalStatus('online', -1);
    throw new RuntimeException('Negative distances must be rejected.');
} catch (InvalidArgumentException) {
}

echo "OLT normalized DTO tests passed.\n";
