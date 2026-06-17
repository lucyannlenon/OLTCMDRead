<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\CDATA\Command\ListPonsCommand as CDataListPonsCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListPonsCommand as DatacomListPonsCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListPonsCommand as FiberhomeListPonsCommand;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListPonsCommand as VSolEponListPonsCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListPonsCommand as VSolGponListPonsCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\ZTE\Command\ListPonsCommand as ZteListPonsCommand;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$cdataConnection = new class implements ConnectionInterface {
    public function exec(string $cmd): string
    {
        return match ($cmd) {
            'show ont info 0/0 1 all' => "0/0  1  1  AA:BB:CC:DD:EE:01 active online success match",
            'show ont info 0/0 2 all' => "0/0  2  1  AA:BB:CC:DD:EE:02 active online success match",
            default => '',
        };
    }

    public function setTimeout(int $timeout): void
    {
    }
};
expect(
    (new CDataListPonsCommand($cdataConnection))->execute() === ['0/0/1', '0/0/2'],
    'CDATA registered PON listing failed.'
);

$datacomConnection = new class implements ConnectionInterface {
    public function exec(string $cmd): string
    {
        return $cmd === 'show running-config service-port | include gpon '
            ? "gpon 1/1/8 onu 58 gem 1 match vlan vlan-id 2000\r\n"
                . "gpon 1/1/2 onu 7 gem 1 match vlan vlan-id 2001\r\n"
                . "gpon 1/1/8 onu 59 gem 1 match vlan vlan-id 2002\r\n"
            : '';
    }

    public function setTimeout(int $timeout): void
    {
    }
};
expect(
    (new DatacomListPonsCommand($datacomConnection))->execute() === ['1/1/2', '1/1/8'],
    'DATACOM registered PON listing failed.'
);

$zteConnection = new class extends ZTEConnection {
    public function __construct()
    {
    }

    public function exec(string $cmd): string|bool
    {
        return match ($cmd) {
            'show card' => "Shelf Slot CfgType CardName Port HardVer Status\r\n"
                . "1 3 GVGH GVGH 2 V1.0.0 INSERVICE",
            'show pon onu information gpon_olt-1/3/1' =>
                "OltIndex    OnuIndex   Admin State  OMCC State  Phase State  Channel\r\n"
                . "----\r\n"
                . "gpon-onu_1/3/1:1  enable  enable  SN(ZTE00000001)  working  0  0\r\n",
            'show pon onu information gpon_olt-1/3/2' =>
                "OltIndex    OnuIndex   Admin State  OMCC State  Phase State  Channel\r\n"
                . "----\r\n"
                . "gpon-onu_1/3/2:7  enable  enable  SN(ZTE00000002)  working  0  0\r\n",
            default => '',
        };
    }
};
expect(
    (new ZteListPonsCommand($zteConnection))->execute() === ['1/3/1', '1/3/2'],
    'ZTE registered PON listing failed.'
);

$fiberhomeConnection = new class extends FiberhomeConnection {
    public function __construct()
    {
    }

    public function getIpOlt(): string
    {
        return '198.51.100.10';
    }

    public function exec(string $cmd): string
    {
        return "NA-NA-11-2\t1\tonline\tAN5506\tFHTT0001\n"
            . "NA-NA-11-3\t2\toffline\tAN5506\tFHTT0002\n"
            . "NA-NA-11-2\t3\tonline\tAN5506\tFHTT0003\n";
    }
};
expect(
    (new FiberhomeListPonsCommand($fiberhomeConnection))->execute() === ['NA-NA-11-2', 'NA-NA-11-3'],
    'Fiberhome registered PON listing failed.'
);

$vsolEponConnection = new class implements VSolEponConnectionInterface {
    public function exec(string $cmd): string|bool
    {
        return match ($cmd) {
            'show onu basic-info all' => "EPON0/1:1   VEND      MODEL-A   AABBCCDDEE01  V1.0  V2.0\n"
                . "EPON0/3:2   VEND      MODEL-B   AABBCCDDEE02  V1.0  V2.0",
            'show onu status all' => "EPON0/1:1   online    aa:bb:cc:dd:ee:01    2080         1365    2026/06/01 08:08:13     2026/06/01 08:07:32     Power Off         6 19:16:07   N/A\n"
                . "EPON0/3:2   offline   aa:bb:cc:dd:ee:02    0            0       N/A                     2026/06/01 08:07:32     Power Off         0 00:00:00   N/A",
            default => '',
        };
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        return '';
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
    }
};
expect(
    (new VSolEponListPonsCommand($vsolEponConnection))->execute() === ['0/1', '0/3'],
    'VSOL EPON registered PON listing failed.'
);

$vsolGponConnection = new class implements VSolGponConnectionInterface {
    public function exec(string $cmd): string|bool
    {
        return $cmd === 'show onu info'
            ? "GPON0/1:1  ROUTER-A    default  sn    VSOL00000001\n"
                . "GPON0/2:2  ROUTER-B    default  sn    VSOL00000002\n"
                . "GPON0/1:3  ROUTER-C    default  sn    VSOL00000003"
            : '';
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        return '';
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
    }
};
expect(
    (new VSolGponListPonsCommand($vsolGponConnection))->execute() === ['0/1', '0/2'],
    'VSOL GPON registered PON listing failed.'
);

echo "OLT registered PON command tests passed.\n";
