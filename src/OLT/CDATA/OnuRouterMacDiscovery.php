<?php

namespace LLENON\OltInformation\OLT\CDATA;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\Command\ListAllOnuCommand;
use LLENON\OltInformation\OLT\CDATA\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\CDATA\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\CDATA\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Dto\Onu;

final class OnuRouterMacDiscovery
{
    private ListAllOnuCommand $listAllOnus;
    private ListOnuMacAddressCommand $listOnuMacs;
    private LocateMacAddressCommand $locateMac;

    public function __construct(ConnectionInterface $connection)
    {
        $this->listAllOnus = new ListAllOnuCommand($connection);
        $this->listOnuMacs = new ListOnuMacAddressCommand($connection);
        $this->locateMac = new LocateMacAddressCommand($connection);
    }

    /**
     * @return array<int, array{onu: Onu, mac_addresses: array<LearnedMacAddress>}>
     */
    public function discoverAll(bool $onlineOnly = true): array
    {
        $results = [];

        foreach ($this->listAllOnus->execute() as $onu) {
            if ($onlineOnly && strtolower($onu->getState()) !== 'online') {
                continue;
            }

            $macAddresses = $this->listOnuMacs->execute(
                $onu->getPon(),
                (int) $onu->getId()
            );
            $onuMac = strtoupper($onu->getGponId());

            $results[] = [
                'onu' => $onu,
                'mac_addresses' => array_values(array_filter(
                    $macAddresses,
                    static fn (LearnedMacAddress $mac): bool => $mac->macAddress !== $onuMac
                )),
            ];
        }

        return $results;
    }

    /**
     * @return array<LearnedMacAddress>
     */
    public function locate(string $macAddress): array
    {
        return $this->locateMac->execute($macAddress);
    }
}
