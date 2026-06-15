<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\LocateMacAddressCommand;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$connection = new class implements ConnectionInterface {
    public array $commands = [];

    public function exec(string $cmd): string
    {
        $this->commands[] = $cmd;

        return match ($cmd) {
            'show running-config service-port | select gpon 1/1/8 | context-match "onu 58 "' =>
                "service-port 931\ngpon 1/1/8 onu 58 gem 1",
            'show mac-address-table' =>
                "service-port-931  11:22:33:44:55:66  2000 dynamic\n"
                . "service-port-100  AA:BB:CC:DD:EE:FF  100 static\n",
            'show mac-address-table mac-address 11:22:33:44:55:66' =>
                "service-port-931  11:22:33:44:55:66  2000 dynamic\n",
            'show running-config service-port 931' =>
                "service-port 931\ngpon 1/1/8 onu 58 gem 1 match vlan vlan-id 2000\n",
            default => '',
        };
    }

    public function setTimeout(int $timeout): void
    {
    }
};

$macs = (new ListOnuMacAddressCommand($connection))->execute('1/1/8', '58');
expect(count($macs) === 1, 'DATACOM ONU MAC filtering failed.');
expect($macs[0]->macAddress === '11:22:33:44:55:66', 'DATACOM MAC normalization failed.');

$location = (new LocateMacAddressCommand($connection))->execute('1122.3344.5566');
expect($location?->pon === '1/1/8', 'DATACOM reverse MAC PON failed.');
expect($location?->onuId === '58', 'DATACOM reverse MAC ONU failed.');

echo "DATACOM MAC discovery tests passed.\n";
