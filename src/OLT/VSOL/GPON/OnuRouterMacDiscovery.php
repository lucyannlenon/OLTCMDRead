<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON;

use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\OnuStatusCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;

final class OnuRouterMacDiscovery
{
    private ListOnuCommand $listOnus;
    private OnuStatusCommand $onuStatus;
    private ListOnuMacAddressCommand $listOnuMacs;
    private LocateMacAddressCommand $locateMac;

    public function __construct(VSolGponConnectionInterface $connection)
    {
        $this->listOnus = new ListOnuCommand($connection);
        $this->onuStatus = new OnuStatusCommand($connection);
        $this->listOnuMacs = new ListOnuMacAddressCommand($connection);
        $this->locateMac = new LocateMacAddressCommand($connection);
    }

    public function discoverAll(bool $onlineOnly = true): array
    {
        $results = [];

        foreach ($this->listOnus->execute() as $onu) {
            $pon = self::ponNumber($onu->getPon());
            $onuId = (int) $onu->getId();
            $status = $this->onuStatus->execute($pon, $onuId);

            if ($onlineOnly && ($status === null || !$status->isOnline())) {
                continue;
            }

            $results[] = [
                'onu' => $onu,
                'status' => $status,
                'mac_addresses' => $this->listOnuMacs->execute($pon, $onuId),
            ];
        }

        return $results;
    }

    public function locate(string $macAddress): mixed
    {
        return $this->locateMac->execute($macAddress);
    }

    private static function ponNumber(string $pon): int
    {
        if (!preg_match('~(?:^|/)(\d+)$~', $pon, $matches)) {
            throw new \UnexpectedValueException("Invalid VSOL GPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
