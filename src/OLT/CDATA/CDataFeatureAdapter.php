<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\CDATA\Command\ListAllOnuCommand;
use LLENON\OltInformation\OLT\CDATA\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\CDATA\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Utils\Discovery\OnuRouterMacDiscovery;
use LLENON\OltInformation\OLT\Utils\Feature\AbstractOltFeatureProvider;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

final readonly class CDataFeatureAdapter extends AbstractOltFeatureProvider implements
    OnuInventoryInterface,
    OnuDiagnosticsInterface,
    OnuMacDiscoveryInterface
{
    private ListAllOnuCommand $listOnu;
    private ListOnuMacAddressCommand $listMac;
    private LocateMacAddressCommand $locateMac;

    public function __construct(
        private CDATAConnection $connection
    ) {
        parent::__construct([
            OltFeature::ONU_LIST,
            OltFeature::ONU_LOOKUP,
            OltFeature::LEARNED_MACS,
            OltFeature::REVERSE_MAC_LOOKUP,
            OltFeature::ROUTER_MAC_DISCOVERY,
        ]);
        $this->listOnu = new ListAllOnuCommand($connection);
        $this->listMac = new ListOnuMacAddressCommand($connection);
        $this->locateMac = new LocateMacAddressCommand($connection);
    }

    public function listOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::ONU_LIST,
            array_map($this->mapOnu(...), $this->listOnu->execute())
        );
    }

    public function findOnu(string $registrationId): OltFeatureResult
    {
        $normalized = MacLocation::normalizeMacAddress($registrationId);
        foreach ($this->listOnus()->value as $onu) {
            if ($onu->registrationId === $normalized) {
                return OltFeatureResult::supported(OltFeature::ONU_LOOKUP, $onu);
            }
        }

        return OltFeatureResult::unavailable(OltFeature::ONU_LOOKUP, 'ONU_NOT_FOUND');
    }

    public function listUnauthorizedOnus(): OltFeatureResult
    {
        return $this->unsupported(OltFeature::UNAUTHORIZED_ONUS);
    }

    public function status(OnuIdentity $onu): OltFeatureResult
    {
        return $this->unsupported(OltFeature::ONU_STATUS);
    }

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult
    {
        return $this->unsupported(OltFeature::OPTICAL_SIGNAL);
    }

    public function ethernetStatus(OnuIdentity $onu, int $port = 1): OltFeatureResult
    {
        return $this->unsupported(OltFeature::ETHERNET_STATE);
    }

    public function vlan(OnuIdentity $onu): OltFeatureResult
    {
        return $this->unsupported(OltFeature::VLAN);
    }

    public function learnedMacs(OnuIdentity $onu): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::LEARNED_MACS,
            array_map(
                static fn ($entry): LearnedMacAddress => new LearnedMacAddress(
                    $entry->macAddress,
                    $entry->vlan,
                    $entry->pon,
                    $entry->onuId,
                    $entry->type
                ),
                $this->listMac->execute($onu->pon, (int) $onu->onuId)
            )
        );
    }

    public function locateMac(string $macAddress): OltFeatureResult
    {
        $entry = $this->locateMac->execute($macAddress)[0] ?? null;
        if ($entry === null) {
            return OltFeatureResult::unavailable(OltFeature::REVERSE_MAC_LOOKUP, 'MAC_NOT_FOUND');
        }

        return OltFeatureResult::supported(
            OltFeature::REVERSE_MAC_LOOKUP,
            new MacLocation(
                $entry->macAddress,
                $entry->pon,
                $entry->onuId,
                $entry->vlan,
                $entry->type
            )
        );
    }

    public function discoverRouterMacs(bool $onlineOnly = true): OltFeatureResult
    {
        return (new OnuRouterMacDiscovery($this, $this))->discover($onlineOnly);
    }

    private function mapOnu(Onu $onu): OnuIdentity
    {
        return new OnuIdentity(
            'CDATA',
            $onu->getPon(),
            $onu->getId(),
            MacLocation::normalizeMacAddress($onu->getGponId()),
            $onu->getState()
        );
    }
}
