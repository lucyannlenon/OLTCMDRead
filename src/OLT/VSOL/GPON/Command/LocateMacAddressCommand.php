<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\MacLocationStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\MacLocation;
use LLENON\OltInformation\OLT\VSOL\GPON\Support\MacAddress;

final class LocateMacAddressCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly MacLocationStringParser $parser = new MacLocationStringParser(),
        private readonly LearnedMacAddressStringParser $tableParser = new LearnedMacAddressStringParser(),
        private readonly ListOnuStringParser $onuParser = new ListOnuStringParser()
    ) {
    }

    public function execute(string $macAddress): ?MacLocation
    {
        $commandMac = MacAddress::forVSolCommand($macAddress);
        $response = $this->connection->exec("show mac address-table address {$commandMac}");
        $results = $response === false ? [] : $this->parser->parse($response);
        $location = $results[0] ?? null;

        if ($location !== null && str_starts_with(strtoupper($location->port), 'GPON')) {
            return $location;
        }

        $normalizedMac = MacAddress::normalize($macAddress);
        $onuResponse = $this->connection->exec('show onu info');

        foreach ($onuResponse === false ? [] : $this->onuParser->parse($onuResponse) as $onu) {
            $pon = self::ponNumber($onu->getPon());
            $table = $this->connection->exec(
                "show mac address-table pon {$pon} {$onu->getId()}"
            );

            foreach ($table === false ? [] : $this->tableParser->parse($table) as $entry) {
                if ($entry->macAddress !== $normalizedMac) {
                    continue;
                }

                return new MacLocation(
                    $entry->macAddress,
                    $entry->vlan,
                    $entry->type,
                    "GPON{$entry->pon}:{$entry->onuId}"
                );
            }
        }

        return $location;
    }

    private static function ponNumber(string $pon): int
    {
        if (!preg_match('~(?:^|/)(\d+)$~', $pon, $matches)) {
            throw new \UnexpectedValueException("Invalid VSOL GPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
