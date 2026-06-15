<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\ZTE\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\ZTE\Command\LocateMacAddressCommand;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$connection = new class implements ConnectionInterface {
    public function exec(string $cmd): string
    {
        return <<<'TEXT'
0011.2233.4455  2004  Dynamic  vport-1/3/5.73:1
AABB.CCDD.EEFF  1000  Static   vport-1/3/6.2:1
TEXT;
    }

    public function setTimeout(int $timeout): void
    {
    }
};

$macs = (new ListOnuMacAddressCommand($connection))->execute('1/3/5', '73');
expect(count($macs) === 1, 'ZTE ONU MAC filtering failed.');
expect($macs[0]->macAddress === '00:11:22:33:44:55', 'ZTE MAC normalization failed.');

$location = (new LocateMacAddressCommand($connection))->execute('0011.2233.4455');
expect($location?->pon === '1/3/5', 'ZTE reverse MAC PON failed.');
expect($location?->onuId === '73', 'ZTE reverse MAC ONU failed.');

echo "ZTE MAC discovery tests passed.\n";
