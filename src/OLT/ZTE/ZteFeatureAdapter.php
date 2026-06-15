<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\OnuEthernetStatus;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Dto\OnuOperationalStatus;
use LLENON\OltInformation\OLT\Dto\OnuOpticalMetrics;
use LLENON\OltInformation\OLT\Utils\Discovery\OnuRouterMacDiscovery;
use LLENON\OltInformation\OLT\Utils\Feature\AbstractOltFeatureProvider;
use LLENON\OltInformation\OLT\ZTE\Command\DistanceOnuCommand;
use LLENON\OltInformation\OLT\ZTE\Command\ListAllOnuCommand;
use LLENON\OltInformation\OLT\ZTE\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\ZTE\Command\ListUnAuthorization;
use LLENON\OltInformation\OLT\ZTE\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\ZTE\Command\OnuEthernetStatusCommand;
use LLENON\OltInformation\OLT\ZTE\Command\SignalOnuCommand;
use LLENON\OltInformation\OLT\ZTE\Command\TemperatureOnuCommand;
use LLENON\OltInformation\OLT\ZTE\Command\VlanOnuCommand;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

final readonly class ZteFeatureAdapter extends AbstractOltFeatureProvider implements
    OnuInventoryInterface,
    OnuDiagnosticsInterface,
    OnuMacDiscoveryInterface
{
    private ListAllOnuCommand $listOnu;
    private ListUnAuthorization $listUnauthorized;
    private ListOnuMacAddressCommand $listMac;
    private LocateMacAddressCommand $locateMac;

    public function __construct(
        private ZTEConnection $connection
    ) {
        parent::__construct([
            OltFeature::ONU_LIST,
            OltFeature::ONU_LOOKUP,
            OltFeature::ONU_STATUS,
            OltFeature::OPTICAL_SIGNAL,
            OltFeature::TEMPERATURE,
            OltFeature::DISTANCE,
            OltFeature::ETHERNET_STATE,
            OltFeature::VLAN,
            OltFeature::UNAUTHORIZED_ONUS,
            OltFeature::LEARNED_MACS,
            OltFeature::REVERSE_MAC_LOOKUP,
            OltFeature::ROUTER_MAC_DISCOVERY,
        ]);
        $this->listOnu = new ListAllOnuCommand($connection);
        $this->listUnauthorized = new ListUnAuthorization($connection);
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
        foreach ($this->listOnus()->value as $onu) {
            if (strcasecmp($onu->registrationId, trim($registrationId)) === 0) {
                return OltFeatureResult::supported(OltFeature::ONU_LOOKUP, $onu);
            }
        }

        return OltFeatureResult::unavailable(OltFeature::ONU_LOOKUP, 'ONU_NOT_FOUND');
    }

    public function listUnauthorizedOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::UNAUTHORIZED_ONUS,
            $this->listUnauthorized->execute()
        );
    }

    public function status(OnuIdentity $onu): OltFeatureResult
    {
        $distance = (new DistanceOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        return OltFeatureResult::supported(
            OltFeature::ONU_STATUS,
            new OnuOperationalStatus($onu->state, $this->integer($distance))
        );
    }

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult
    {
        $signal = (new SignalOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        $temperature = (new TemperatureOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        $rxPower = $this->number($signal);
        if ($rxPower === null) {
            return OltFeatureResult::unavailable(OltFeature::OPTICAL_SIGNAL, 'OPTICAL_UNAVAILABLE');
        }

        return OltFeatureResult::supported(
            OltFeature::OPTICAL_SIGNAL,
            new OnuOpticalMetrics($rxPower, temperatureCelsius: $this->number($temperature))
        );
    }

    public function ethernetStatus(OnuIdentity $onu, int $port = 1): OltFeatureResult
    {
        $entry = (new OnuEthernetStatusCommand($this->connection))
            ->execute($onu->pon, $onu->onuId)[$port - 1] ?? null;
        if ($entry === null) {
            return OltFeatureResult::unavailable(OltFeature::ETHERNET_STATE, 'ETHERNET_PORT_UNAVAILABLE');
        }

        return OltFeatureResult::supported(
            OltFeature::ETHERNET_STATE,
            new OnuEthernetStatus('up', $this->speedMbps($entry->speed), $entry->speed)
        );
    }

    public function vlan(OnuIdentity $onu): OltFeatureResult
    {
        $raw = (new VlanOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        $vlan = $this->integer($raw);
        return $vlan === null
            ? OltFeatureResult::unavailable(OltFeature::VLAN, 'VLAN_UNAVAILABLE')
            : OltFeatureResult::supported(OltFeature::VLAN, $vlan);
    }

    public function learnedMacs(OnuIdentity $onu): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::LEARNED_MACS,
            $this->listMac->execute($onu->pon, $onu->onuId)
        );
    }

    public function locateMac(string $macAddress): OltFeatureResult
    {
        $location = $this->locateMac->execute($macAddress);
        return $location === null
            ? OltFeatureResult::unavailable(OltFeature::REVERSE_MAC_LOOKUP, 'MAC_NOT_FOUND')
            : OltFeatureResult::supported(OltFeature::REVERSE_MAC_LOOKUP, $location);
    }

    public function discoverRouterMacs(bool $onlineOnly = true): OltFeatureResult
    {
        return (new OnuRouterMacDiscovery($this, $this))->discover($onlineOnly);
    }

    private function mapOnu(Onu $onu): OnuIdentity
    {
        return new OnuIdentity(
            'ZTE',
            $onu->getPon(),
            $onu->getId(),
            $onu->getGponId(),
            $onu->getState()
        );
    }

    private function number(string $value): ?float
    {
        return preg_match('/-?\d+(?:\.\d+)?/', $value, $matches) === 1
            ? (float) $matches[0]
            : null;
    }

    private function integer(string $value): ?int
    {
        $number = $this->number($value);
        return $number === null ? null : (int) round($number);
    }

    private function speedMbps(string $value): ?int
    {
        $speed = $this->integer($value);
        if ($speed === null) {
            return null;
        }

        return stripos($value, 'gbit') !== false ? $speed * 1000 : $speed;
    }
}
