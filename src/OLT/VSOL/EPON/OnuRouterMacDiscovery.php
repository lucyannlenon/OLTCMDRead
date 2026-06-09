<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON;

use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class OnuRouterMacDiscovery
{
    private ListOnuCommand $listOnus;
    private ListOnuMacAddressCommand $listOnuMacs;
    private LocateMacAddressCommand $locateMac;

    public function __construct(VSolEponConnectionInterface $connection)
    {
        $this->listOnus = new ListOnuCommand($connection);
        $this->listOnuMacs = new ListOnuMacAddressCommand($connection);
        $this->locateMac = new LocateMacAddressCommand($connection);
    }

    public function discoverAll(bool $onlineOnly = true): array
    {
        $results = [];

        foreach ($this->listOnus->execute() as $onu) {
            if ($onlineOnly && strtolower($onu->getState()) !== 'online') {
                continue;
            }

            $pon = self::ponNumber($onu->getPon());
            $macs = array_values(array_filter(
                $this->listOnuMacs->execute($pon, (int) $onu->getId()),
                static fn ($entry): bool =>
                    $entry->macAddress !== MacAddress::normalize($onu->getGponId())
            ));

            $results[] = [
                'onu' => $onu,
                'mac_addresses' => $macs,
            ];
        }

        return $results;
    }

    public function locate(string $macAddress): ?object
    {
        return $this->locateMac->execute($macAddress);
    }

    private static function ponNumber(string $pon): int
    {
        if (!preg_match('~(?:^|/)(\d+)$~', $pon, $matches)) {
            throw new \UnexpectedValueException("Invalid VSOL EPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
