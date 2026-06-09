<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\GlobalMacAddressStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\MacLocation;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class LocateMacAddressCommand
{
    private ListOnuCommand $listOnus;
    private ListOnuMacAddressCommand $listOnuMacs;

    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly GlobalMacAddressStringParser $globalParser = new GlobalMacAddressStringParser()
    ) {
        $this->listOnus = new ListOnuCommand($connection);
        $this->listOnuMacs = new ListOnuMacAddressCommand($connection);
    }

    public function execute(string $macAddress): ?MacLocation
    {
        $normalized = MacAddress::normalize($macAddress);
        $response = $this->connection->exec('show mac address-table');
        $candidates = $response === false ? [] : $this->globalParser->parse($response);

        foreach ($candidates as $candidate) {
            if ($candidate['mac_address'] !== $normalized) {
                continue;
            }

            $pon = self::ponNumber($candidate['pon']);
            foreach ($this->listOnus->execute($pon) as $onu) {
                foreach ($this->listOnuMacs->execute($pon, (int) $onu->getId()) as $entry) {
                    if ($entry->macAddress !== $normalized) {
                        continue;
                    }

                    return new MacLocation(
                        $entry->macAddress,
                        $entry->vlan,
                        $entry->type,
                        $entry->pon,
                        $entry->onuId
                    );
                }
            }
        }

        return null;
    }

    private static function ponNumber(string $pon): int
    {
        if (!preg_match('~(?:^|/)(\d+)$~', $pon, $matches)) {
            throw new \UnexpectedValueException("Invalid VSOL EPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
