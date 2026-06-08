<?php

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\Command\ListAllOnuCommand;
use LLENON\OltInformation\OLT\CDATA\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\CDATA\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\CDATA\OnuRouterMacDiscovery;

require __DIR__ . '/../vendor/autoload.php';

final class FakeCDATAConnection implements ConnectionInterface
{
    /** @var array<string> */
    public array $commands = [];

    public function exec(string $cmd): string
    {
        $this->commands[] = $cmd;

        if (str_starts_with($cmd, 'show ont info')) {
            $port = preg_match('/\s([1-8])\s+all$/', $cmd, $matches) ? $matches[1] : '1';
            return "0/0  {$port}  1  AA:BB:CC:DD:EE:0{$port} active online success match";
        }

        return <<<'TEXT'
MAC                 VLAN   Port          ONT-Id  MAC-Type
11:22:33:44:55:66   100    pon0/0/1      1       dynamic
AA:BB:CC:DD:EE:01   100    pon0/0/1      1       dynamic
TEXT;
    }

    public function setTimeout(int $timeout): void
    {
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$onuOutput = <<<'TEXT'
-----------------------------------------------------------------------------
  F/S  P  ONT MAC               Control   Run        Config   Match     Desc
          ID                    flag      state      state    state
 ----------------------------------------------------------------------------
  0/0  1  1   C4:70:0B:6C:80:48 active    online     success  match
  0/0  1  6   00:6D:61:BB:0D:E8 active    offline    failed   match
-----------------------------------------------------------------------------
TEXT;

$onus = (new ListOnuStringParser())->parse($onuOutput);
expect(count($onus) === 2, 'Expected two parsed ONUs.');
expect($onus[0]->getPon() === '0/0/1', 'Unexpected PON address.');
expect($onus[0]->getId() === '1', 'Unexpected ONU ID.');
expect($onus[0]->getGponId() === 'C4:70:0B:6C:80:48', 'Unexpected ONU MAC.');
expect($onus[0]->getState() === 'online', 'Unexpected ONU state.');

$macOutput = <<<'TEXT'
-----------------------------------------------------------------------------
 Total: 2
-----------------------------------------------------------------------------
 MAC                 VLAN   Port          ONT-Id  MAC-Type
-----------------------------------------------------------------------------
 E4:60:4D:72:71:FF   100    pon0/0/1      1       dynamic
 C4:70:0B:6C:80:49   100    pon0/0/1      1       dynamic
-----------------------------------------------------------------------------
TEXT;

$macs = (new LearnedMacAddressStringParser())->parse($macOutput);
expect(count($macs) === 2, 'Expected two parsed MAC addresses.');
expect($macs[0]->macAddress === 'E4:60:4D:72:71:FF', 'Unexpected learned MAC.');
expect($macs[0]->vlan === '100', 'Unexpected VLAN.');
expect($macs[0]->pon === '0/0/1', 'Unexpected learned MAC PON.');
expect($macs[0]->onuId === '1', 'Unexpected learned MAC ONU ID.');

$connection = new FakeCDATAConnection();
$allOnus = (new ListAllOnuCommand($connection))->execute();
expect(count($allOnus) === 8, 'Expected one ONU from each of the eight PONs.');
expect(
    $connection->commands[0] === 'show ont info 0/0 1 all'
    && $connection->commands[7] === 'show ont info 0/0 8 all',
    'ListAllOnuCommand did not iterate through all PONs.'
);

$connection = new FakeCDATAConnection();
(new ListOnuMacAddressCommand($connection))->execute('0/0/1', 1);
expect(
    $connection->commands === ['show mac-address ont 0/0/1 1'],
    'Unexpected ONU MAC command.'
);

$connection = new FakeCDATAConnection();
(new LocateMacAddressCommand($connection))->execute('1122.3344.5566');
expect(
    $connection->commands === ['show location 11:22:33:44:55:66'],
    'Unexpected MAC location command.'
);

$connection = new FakeCDATAConnection();
$discovered = (new OnuRouterMacDiscovery($connection))->discoverAll();
expect(count($discovered) === 8, 'Expected discovery results for eight online ONUs.');
expect(
    count($discovered[0]['mac_addresses']) === 1
    && $discovered[0]['mac_addresses'][0]->macAddress === '11:22:33:44:55:66',
    'Discovery did not remove the ONU MAC from learned MAC addresses.'
);

echo "CDATA adapter tests passed.\n";
