<?php

use LLENON\OltInformation\Adapters\VSolOLTCmd;
use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\IncompatibleOltCliProfileException;
use LLENON\OltInformation\Exceptions\MissingOltVersionConfigurationException;
use LLENON\OltInformation\Exceptions\UnsupportedOltFirmwareException;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\EthernetStatusCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\OnuStatusCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\OpticalInfoCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\BasicOnuInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\EthernetStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\GlobalMacAddressStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\OnuStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\OnuRouterMacDiscovery;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

require __DIR__ . '/../vendor/autoload.php';

final class FakeVSolEponConnection implements VSolEponConnectionInterface
{
    public array $commands = [];
    public bool $disconnected = false;

    public function exec(string $cmd): string|bool
    {
        $this->commands[] = $cmd;

        return match ($cmd) {
            'show onu status aabb:ccdd:ee01' => self::statusRow(),
            'show onu status all', 'show onu status pon 1,all' => self::statusRows(),
            'show onu basic-info all', 'show onu basic-info pon 1,all' => self::basicRows(),
            'show onu opm-diag pon 1,1' => self::opticalRow(),
            'show mac address-table' => <<<'TEXT'
1        11:22:33:44:55:66  Dynamic     epon0/1   255            1
TEXT,
            default => throw new RuntimeException("Unexpected command: {$cmd}"),
        };
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        $this->commands[] = "pon {$pon}: {$cmd}";

        return match ($cmd) {
            'show onu 1 mac-address-table' => <<<'TEXT'
1       100    AA:BB:CC:DD:EE:01    EPON0/1   1      120
2       100    11:22:33:44:55:66    EPON0/1   1      100
TEXT,
            'show onu 2 mac-address-table' => '',
            'show onu 1 ctc eth 1 port_info' => <<<'TEXT'
PortId    Link State
1         UP
TEXT,
            'show onu 1 ctc eth 1 autoneg' => <<<'TEXT'
PortId    AutoNeg
1         enabled
TEXT,
            'show onu 1 ctc eth 1 loopdetect' => <<<'TEXT'
PortId    Loopdetect
1         activated
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

    public static function statusRow(): string
    {
        return <<<'TEXT'
EPON0/1:1   online    aa:bb:cc:dd:ee:01    2080         1365    2026/06/01 08:08:13     2026/06/01 08:07:32     Power Off         6 19:16:07   N/A
TEXT;
    }

    public static function statusRows(): string
    {
        return self::statusRow() . "\n"
            . 'EPON0/1:2   offline   aa:bb:cc:dd:ee:02    0            0       N/A                     2026/06/01 08:07:32     Power Off         0 00:00:00   N/A';
    }

    public static function basicRows(): string
    {
        return <<<'TEXT'
EPON0/1:1   VEND      MODEL-A   AABBCCDDEE01  V1.0  V2.0
TEXT;
    }

    public static function opticalRow(): string
    {
        return <<<'TEXT'
EPON0/1:1   27.45             3.39                14.15                 1.90            -14.72
TEXT;
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

function eponOlt(?string $profile, ?string $firmware, string $model = OltModel::VSOL): OLT
{
    return new OLT(
        'user',
        'secret',
        $model,
        '192.0.2.1',
        '23',
        'telnet',
        'test',
        $profile,
        $firmware
    );
}

$registry = new OltCliProfileRegistry();
expectException(
    MissingOltVersionConfigurationException::class,
    static fn () => $registry->resolve(eponOlt(null, 'V1.01.51_230922190137'))
);
expectException(
    MissingOltVersionConfigurationException::class,
    static fn () => $registry->resolve(eponOlt(OltCliProfile::VSOL_EPON_CLI_V1, null))
);
expectException(
    IncompatibleOltCliProfileException::class,
    static fn () => $registry->resolve(
        eponOlt(OltCliProfile::VSOL_EPON_CLI_V1, 'V1.01.51_230922190137', OltModel::CDATA)
    )
);
expectException(
    UnsupportedOltFirmwareException::class,
    static fn () => $registry->resolve(eponOlt(OltCliProfile::VSOL_EPON_CLI_V1, 'V9'))
);
expectException(
    InvalidArgumentException::class,
    static fn () => new VSolEponConnection(
        new OLT(
            'user',
            'secret',
            OltModel::VSOL,
            '192.0.2.1',
            '22',
            'ssh',
            'test',
            OltCliProfile::VSOL_EPON_CLI_V1,
            'V1.01.51_230922190137'
        )
    )
);

$status = (new OnuStatusStringParser())->parse(FakeVSolEponConnection::statusRow())[0];
expect($status->pon === '0/1', 'Unexpected EPON status PON.');
expect($status->onuId === 1, 'Unexpected EPON ONU ID.');
expect($status->isOnline(), 'Online EPON ONU was not recognized.');
expect($status->distance === '2080', 'Unexpected EPON distance.');
expect($status->aliveTime === '6 19:16:07', 'Unexpected EPON alive time.');

$basic = (new BasicOnuInfoStringParser())->parse(FakeVSolEponConnection::basicRows());
expect(count($basic) === 1, 'Expected one online EPON basic ONU row.');
expect($basic[0]->getGponId() === 'AA:BB:CC:DD:EE:01', 'Unexpected EPON registration MAC.');

$optical = (new OpticalInfoStringParser())->parse(FakeVSolEponConnection::opticalRow())[0];
expect($optical->temperature === '27.45', 'Unexpected EPON temperature.');
expect($optical->rxOpticalLevel === '-14.72', 'Unexpected EPON RX level.');

$ethernet = (new EthernetStatusStringParser())->parse(<<<'TEXT'
PortId    Link State
1         UP
PortId    AutoNeg
1         enabled
PortId    Loopdetect
1         activated
TEXT)[0];
expect($ethernet->speed === 'N/A', 'EPON speed must remain unavailable.');
expect($ethernet->status === 'up', 'Unexpected EPON Ethernet state.');
expect($ethernet->speedConfig === 'enabled', 'Unexpected EPON autoneg state.');
expect($ethernet->loopStatus === 'activated', 'Unexpected EPON loop state.');

$macs = (new LearnedMacAddressStringParser())->parse(<<<'TEXT'
1       100    11:22:33:44:55:66    EPON0/1   1      100
TEXT);
expect($macs[0]->macAddress === '11:22:33:44:55:66', 'Unexpected learned EPON MAC.');
expect($macs[0]->onuId === '1', 'Unexpected learned EPON ONU ID.');

$global = (new GlobalMacAddressStringParser())->parse(
    "0 \r\e[8C11:22:33:44:55:66  Dynamic     epon0/1   255            1"
);
expect($global === [], 'Raw terminal control sequence must be cleaned by the connection.');
$clean = VSolEponConnection::cleanTerminalOutput(
    "0 \r\e[8C11:22:33:44:55:66  Dynamic     epon0/1   255            1"
);
$global = (new GlobalMacAddressStringParser())->parse($clean);
expect($global[0]['pon'] === '0/1', 'Unexpected global MAC PON.');

$connection = new FakeVSolEponConnection();
expect((new OnuStatusCommand($connection))->execute('AA:BB:CC:DD:EE:01')?->onuId === 1, 'Status command failed.');
expect(count((new ListOnuCommand($connection))->execute()) === 2, 'List ONU command failed.');
$listedOnus = (new ListOnuCommand($connection))->execute();
expect($listedOnus[1]->getState() === 'offline', 'Offline ONU must remain in the list.');
expect($listedOnus[1]->getModel() === '', 'Offline ONU without basic-info must use an empty model.');
expect((new OpticalInfoCommand($connection))->execute(1, 1)?->rxOpticalLevel === '-14.72', 'Optical command failed.');
expect((new EthernetStatusCommand($connection))->execute(1, 1)?->status === 'up', 'Ethernet command failed.');
expect(count((new ListOnuMacAddressCommand($connection))->execute(1, 1)) === 2, 'ONU MAC command failed.');

$location = (new LocateMacAddressCommand($connection))->execute('11:22:33:44:55:66');
expect($location?->pon === '0/1', 'Reverse EPON MAC location failed.');
expect($location?->onuId === '1', 'Reverse EPON MAC ONU ID failed.');

$connection = new FakeVSolEponConnection();
$discovery = (new OnuRouterMacDiscovery($connection))->discoverAll();
expect(count($discovery) === 1, 'Online-only EPON discovery must skip offline ONUs.');
expect(count($discovery[0]['mac_addresses']) === 1, 'ONU registration MAC must be excluded.');
expect(
    $discovery[0]['mac_addresses'][0]->macAddress === '11:22:33:44:55:66',
    'Unexpected EPON router MAC candidate.'
);

$connection = new FakeVSolEponConnection();
$client = new Client('customer', 'AA:BB:CC:DD:EE:01', null);
$result = (new VSolOLTCmd(
    eponOlt(OltCliProfile::VSOL_EPON_CLI_V1, 'V1.01.51_230922190137'),
    $client,
    $connection
))->getDadosDoCliente();

expect($result->slot === 0, 'Unexpected client slot.');
expect($result->pon === 1, 'Unexpected client PON.');
expect($result->onuPosition === 1, 'Unexpected client ONU ID.');
expect($result->status === 'ONLINE', 'Unexpected client status.');
expect($result->distance === '2080m', 'Unexpected client distance.');
expect($result->uptime === '6 19:16:07', 'Unexpected client uptime.');
expect($result->signal === '-14.72dBm', 'Unexpected client signal.');
expect($result->onuTemperatura === '27.45', 'Unexpected client temperature.');
expect($result->ethernet->getSpeed() === 'N/A', 'Unexpected client Ethernet speed.');
expect($result->ethernet->getStatus() === 'up', 'Unexpected client Ethernet status.');
expect($connection->disconnected, 'EPON adapter must always disconnect.');

echo "VSOL EPON adapter tests passed.\n";
