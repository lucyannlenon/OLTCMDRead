<?php

declare(strict_types=1);

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class FakeFiberhomeConnection extends FiberhomeConnection
{
    /** @var list<string> */
    public array $commands = [];

    /**
     * @param array<string, string> $responses
     */
    public function __construct(
        private array $responses,
        private string $ipOlt = '10.99.99.10'
    ) {
    }

    public function getIpOlt(): string
    {
        return $this->ipOlt;
    }

    public function exec(string $cmd): string
    {
        $this->commands[] = $cmd;
        return $this->responses[$cmd] ?? '';
    }

    public function setTimeout(int $timeout): void
    {
    }
}

$sampleOutput = <<<'TEXT'
M  CTAG COMPLD
MACADDR    VLAN    TYPE       GEMPORT    UNI
11:22:33:44:55:66    2000    dynamic    GEM-1    UNI-1
C4:70:0B:6C:80:48    2000    dynamic    GEM-1    UNI-1
BAD-LINE-WITHOUT-MAC
GG:22:33:44:55:66    3000    dynamic    GEM-2    UNI-2
EN=0   ENDESC=No error
TEXT;

$parsed = (new LearnedMacAddressStringParser())->parse($sampleOutput);
expect(count($parsed) === 2, 'Fiberhome parser should skip malformed and invalid MAC lines.');
expect($parsed[0]['macAddress'] === '11:22:33:44:55:66', 'Fiberhome parser should normalize MACs.');
expect($parsed[0]['vlan'] === '2000', 'Fiberhome parser should read VLAN.');
expect($parsed[0]['type'] === 'dynamic', 'Fiberhome parser should read MAC type.');
expect($parsed[0]['gemId'] === '1', 'Fiberhome parser should derive GEM id.');
expect($parsed[0]['uniPort'] === '1', 'Fiberhome parser should derive UNI port.');

$pon = 'NA-NA-11-2';
$onuMac = 'C4:70:0B:6C:80:48';
$vlanCommand = "LST-PORTVLAN::OLTID=10.99.99.10,PONID={$pon},ONUIDTYPE=MAC,ONUID={$onuMac},ONUPORT=NA-NA-NA-1:CTAG::;";
$commandString = "LST-PORTMACADDRESS::OLTID=10.99.99.10,PONID={$pon},ONUIDTYPE=MAC,ONUID={$onuMac},PORTID=NA-NA-NA-1,VLAN=100:CTAG::;";

$connection = new FakeFiberhomeConnection([
    $vlanCommand => <<<'TEXT'
M  CTAG COMPLD
--------------------------------------------------------------------------------
ONUIP	OLTID	PONID	ONUID	ONUPORT	SVLAN	CVLAN	VPI	VCI	UV
--	10.99.99.10	1-1-11-2	C4:70:0B:6C:80:48	NA-NA-NA-1	--	100	--	--	--
--------------------------------------------------------------------------------
TEXT,
    $commandString => $sampleOutput,
]);

$macs = (new ListOnuMacAddressCommand($connection))->execute($pon, $onuMac);
expect($connection->commands === [$vlanCommand, $commandString], 'Fiberhome command string mismatch.');
expect(count($macs) === 1, 'Fiberhome command should filter out the ONU registration MAC.');
expect($macs[0]->macAddress === '11:22:33:44:55:66', 'Fiberhome learned MAC normalization failed.');
expect($macs[0]->pon === $pon, 'Fiberhome learned MAC should preserve the PON.');
expect($macs[0]->onuId === $onuMac, 'Fiberhome learned MAC should preserve the ONU identifier.');

$emptyConnection = new FakeFiberhomeConnection([
    $vlanCommand => <<<'TEXT'
M  CTAG COMPLD
--------------------------------------------------------------------------------
ONUIP	OLTID	PONID	ONUID	ONUPORT	SVLAN	CVLAN	VPI	VCI	UV
--	10.99.99.10	1-1-11-2	C4:70:0B:6C:80:48	NA-NA-NA-1	--	100	--	--	--
--------------------------------------------------------------------------------
TEXT,
    $commandString => "M  CTAG COMPLD\nEN=0   ENDESC=No error",
]);
expect(
    (new ListOnuMacAddressCommand($emptyConnection))->execute($pon, $onuMac) === [],
    'Fiberhome empty learned-MAC response should return an empty list.'
);

$noVlanConnection = new FakeFiberhomeConnection([
    $vlanCommand => "M  CTAG COMPLD\nEN=0   ENDESC=No error",
]);
expect(
    (new ListOnuMacAddressCommand($noVlanConnection))->execute($pon, $onuMac) === [],
    'Fiberhome command should return an empty list when VLAN lookup is unavailable.'
);

try {
    (new ListOnuMacAddressCommand($connection))->execute('   ', $onuMac);
    throw new RuntimeException('Blank PON should be rejected.');
} catch (InvalidArgumentException) {
}

try {
    (new ListOnuMacAddressCommand($connection))->execute($pon, '   ');
    throw new RuntimeException('Blank ONU MAC should be rejected.');
} catch (InvalidArgumentException) {
}

echo "Fiberhome ONU MAC command tests passed.\n";
