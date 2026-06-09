<?php

use LLENON\OltInformation\Adapters\VSolOLTGPONCmd;
use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\IncompatibleOltCliProfileException;
use LLENON\OltInformation\Exceptions\MissingOltVersionConfigurationException;
use LLENON\OltInformation\Exceptions\UnsupportedOltFirmwareException;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\DistanceStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\EthernetStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\FindOnuStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\MacLocationStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\OnuStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\UptimeStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\OnuRouterMacDiscovery;
use LLENON\OltInformation\Versioning\OltCliProfileDefinition;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

require __DIR__ . '/../vendor/autoload.php';

final class FakeVSolGponConnection implements VSolGponConnectionInterface
{
    public array $commands = [];
    public bool $disconnected = false;

    public function exec(string $cmd): string|bool
    {
        $this->commands[] = $cmd;

        return match (true) {
            $cmd === 'show onu info' => <<<'TEXT'
Onuindex   Model       Profile  Mode  AuthInfo
GPON0/1:1  ROUTER-A    default  sn    VSOL00000001
GPON0/2:2  ROUTER-B    default  sn    VSOL00000002
TEXT,
            str_starts_with($cmd, 'onu search ') =>
                "pon 1 onu 1 sn VSOL00000001 Online\n--------------search end----------------",
            $cmd === 'show onu state 1 1' =>
                "1/1/1:1 enable enable working 1(GPON)",
            $cmd === 'show onu state 2 2' =>
                "1/1/2:2 enable disable offline 2(GPON)",
            $cmd === 'show mac address-table pon 1 1' =>
                "1122.3344.5566 100 Dynamic GPON0/1:1 1 129",
            $cmd === 'show mac address-table pon 2 2' => '',
            str_starts_with($cmd, 'show mac address-table address ') => <<<'TEXT'
VLAN: 100
MAC Address: 1122:3344:5566
Type: Dynamic
Port: GE0/17
TEXT,
            default => throw new RuntimeException("Unexpected command: {$cmd}"),
        };
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        $this->commands[] = "pon {$pon}: {$cmd}";

        return match ($cmd) {
            'show onu 1 optical_info' => <<<'TEXT'
Rx optical level: -20.125(dBm)
Tx optical level: 1.500(dBm)
Power feed voltage: 3.30(V)
Laser bias current: 14.250(mA)
Temperature: 42.750(C)
TEXT,
            'show onu 1 distance' => 'onu 1 Distance: 934m',
            'show onu 1 time-stamp' =>
                '1/1 2026:06:01 10:00:00 2026:05:30 09:00:00 8 01:02:03',
            'show onu 1 eth 1' => <<<'TEXT'
Speed status: full-1000
Operate status: enable
Speed config: auto
Ethernet loop: disable
TEXT,
            default => throw new RuntimeException("Unexpected PON command: {$cmd}"),
        };
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
        $this->disconnected = true;
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectException(string $class, callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        expect(
            $exception instanceof $class,
            "Expected {$class}, got " . $exception::class . '.'
        );
        return;
    }

    throw new RuntimeException("Expected exception {$class}.");
}

function olt(?string $profile, ?string $firmware, string $model = OltModel::VSOLGPON): OLT
{
    return new OLT('user', 'secret', $model, '192.0.2.1', '22', 'ssh', 'test', $profile, $firmware);
}

$registry = new OltCliProfileRegistry();
expectException(
    MissingOltVersionConfigurationException::class,
    static fn () => $registry->resolve(olt(null, 'V2.1.8R'))
);
expectException(
    MissingOltVersionConfigurationException::class,
    static fn () => $registry->resolve(olt(OltCliProfile::VSOL_GPON_CLI_V2, null))
);
expectException(
    IncompatibleOltCliProfileException::class,
    static fn () => $registry->resolve(
        olt(OltCliProfile::VSOL_GPON_CLI_V2, 'V2.1.8R', OltModel::CDATA)
    )
);
expectException(
    UnsupportedOltFirmwareException::class,
    static fn () => $registry->resolve(olt(OltCliProfile::VSOL_GPON_CLI_V2, 'V9.9.9'))
);
expectException(
    UnsupportedOltFirmwareException::class,
    static fn () => new VSolOLTGPONCmd(
        olt(OltCliProfile::VSOL_GPON_CLI_V2, 'V9.9.9'),
        new Client('customer', null, 'VSOL00000001'),
        new FakeVSolGponConnection()
    )
);

$multiVersionRegistry = new OltCliProfileRegistry([
    new OltCliProfileDefinition('TEST_PROFILE', OltModel::VSOLGPON, ['V1', 'V2']),
]);
expect(
    $multiVersionRegistry->resolve(olt('TEST_PROFILE', 'v2'))->id === 'TEST_PROFILE',
    'One CLI profile must support multiple exact firmware versions.'
);

$onus = (new ListOnuStringParser())->parse(
    "GPON0/1:1 ROUTER-A default sn VSOL00000001\n"
    . "GPON0/2:2 ROUTER-B default sn VSOL00000002"
);
expect(count($onus) === 2, 'Expected two VSOL ONUs.');
expect($onus[0]->getPon() === '0/1', 'Unexpected VSOL PON.');
expect($onus[0]->getId() === '1', 'Unexpected VSOL ONU ID.');
expect($onus[0]->getGponId() === 'VSOL00000001', 'Unexpected VSOL serial.');

$found = (new FindOnuStringParser())->parse('pon 1 onu 1 sn VSOL00000001 Online');
expect($found[0]->getState() === 'online', 'Unexpected search state.');

$status = (new OnuStatusStringParser())->parse(
    '1/1/1:1 enable enable working 1(GPON)'
)[0];
expect($status->isOnline(), 'Working ONU must be online.');

$optical = (new OpticalInfoStringParser())->parse(<<<'TEXT'
Rx optical level: -20.125(dBm)
Tx optical level: 1.500(dBm)
Power feed voltage: 3.30(V)
Laser bias current: 14.250(mA)
Temperature: 42.750(C)
TEXT)[0];
expect($optical->rxOpticalLevel === '-20.125', 'Unexpected RX optical level.');
expect($optical->temperature === '42.750', 'Unexpected temperature.');
expect((new DistanceStringParser())->parse('Distance: 934m') === ['934'], 'Unexpected distance.');
expect(
    (new UptimeStringParser())->parse(
        '1/1 2026:06:01 10:00:00 2026:05:30 09:00:00 8 01:02:03'
    ) === ['8 01:02:03'],
    'Unexpected uptime.'
);

$ethernet = (new EthernetStatusStringParser())->parse(<<<'TEXT'
Speed status: full-1000
Operate status: enable
Speed config: auto
Ethernet loop: disable
TEXT)[0];
expect($ethernet->speed === 'full-1000', 'Unexpected Ethernet speed.');

$macs = (new LearnedMacAddressStringParser())->parse(
    '1122.3344.5566 100 Dynamic GPON0/1:1 1 129'
);
expect($macs[0]->macAddress === '11:22:33:44:55:66', 'Unexpected normalized MAC.');
expect($macs[0]->pon === '0/1', 'Unexpected MAC PON.');

$location = (new MacLocationStringParser())->parse(<<<'TEXT'
VLAN: 100
MAC Address: 1122:3344:5566
Type: Dynamic
Port: GPON0/1:1
TEXT)[0];
expect($location->port === 'GPON0/1:1', 'Unexpected MAC location port.');

$connection = new FakeVSolGponConnection();
expect(count((new ListOnuCommand($connection))->execute()) === 2, 'List command failed.');
expect(count((new ListOnuMacAddressCommand($connection))->execute(1, 1)) === 1, 'MAC list failed.');
expect(
    (new LocateMacAddressCommand($connection))->execute('11:22:33:44:55:66')?->port
        === 'GPON0/1:1',
    'MAC location failed.'
);
expect(
    in_array('show mac address-table address 1122:3344:5566', $connection->commands, true),
    'Unexpected VSOL MAC command format.'
);

$connection = new FakeVSolGponConnection();
$discovery = (new OnuRouterMacDiscovery($connection))->discoverAll();
expect(count($discovery) === 1, 'Online-only discovery must skip offline ONUs.');
expect(count($discovery[0]['mac_addresses']) === 1, 'Discovery MACs are missing.');

$connection = new FakeVSolGponConnection();
$client = new Client('customer', null, 'VSOL00000001');
$result = (new VSolOLTGPONCmd(
    olt(OltCliProfile::VSOL_GPON_CLI_V2, 'V2.1.8R'),
    $client,
    $connection
))->getDadosDoCliente();

expect($result->slot === 0, 'Unexpected client slot.');
expect($result->pon === 1, 'Unexpected client PON.');
expect($result->onuPosition === 1, 'Unexpected client ONU ID.');
expect($result->status === 'ONLINE', 'Unexpected client status.');
expect($result->signal === '-20.125dBm', 'Unexpected client signal.');
expect($result->onuTemperatura === '42.750', 'Unexpected client temperature.');
expect($result->distance === '934m', 'Unexpected client distance.');
expect($result->uptime === '8 01:02:03', 'Unexpected client uptime.');
expect($result->ethernet->getSpeed() === 'full-1000', 'Unexpected Ethernet speed.');
expect($connection->disconnected, 'Adapter must disconnect after reading data.');

echo "VSOL GPON adapter tests passed.\n";
