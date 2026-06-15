<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\DATACOM\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListUnAuthorization;
use LLENON\OltInformation\OLT\DATACOM\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\DetailInfoOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\GetVlanCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\OnuEthernetStatusCommand;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuEthernetStatus;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Dto\OnuOperationalStatus;
use LLENON\OltInformation\OLT\Dto\OnuOpticalMetrics;
use LLENON\OltInformation\OLT\Utils\Discovery\OnuRouterMacDiscovery;
use LLENON\OltInformation\OLT\Utils\Feature\AbstractOltFeatureProvider;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

final readonly class DatacomFeatureAdapter extends AbstractOltFeatureProvider implements
    OnuInventoryInterface,
    OnuDiagnosticsInterface,
    OnuMacDiscoveryInterface
{
    private ListOnuCommand $listOnu;
    private ListUnAuthorization $listUnauthorized;
    private ListOnuMacAddressCommand $listMac;
    private LocateMacAddressCommand $locateMac;

    public function __construct(
        private DATACOMConnection $connection
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
        $this->listOnu = new ListOnuCommand($connection);
        $this->listUnauthorized = new ListUnAuthorization($connection);
        $this->listMac = new ListOnuMacAddressCommand($connection);
        $this->locateMac = new LocateMacAddressCommand($connection);
    }

    public function listOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::ONU_LIST,
            array_map($this->mapOnu(...), $this->listOnu->execute(null))
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
        $detail = (new DetailInfoOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        if (!is_array($detail) || !isset($detail['status'])) {
            return OltFeatureResult::unavailable(OltFeature::ONU_STATUS, 'STATUS_UNAVAILABLE');
        }

        return OltFeatureResult::supported(
            OltFeature::ONU_STATUS,
            new OnuOperationalStatus(
                (string) $detail['status'],
                $this->integer($detail['distance'] ?? null),
                $this->duration($detail['uptime'] ?? null)
            )
        );
    }

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult
    {
        $detail = (new DetailInfoOnuCommand($this->connection))->execute($onu->pon, $onu->onuId);
        if (!is_array($detail)) {
            return OltFeatureResult::unavailable(OltFeature::OPTICAL_SIGNAL, 'OPTICAL_UNAVAILABLE');
        }

        $metrics = new OnuOpticalMetrics(
            $this->number($detail['signal'] ?? null),
            $this->number($detail['tx_power'] ?? null),
            $this->number($detail['temperature'] ?? null)
        );

        return $metrics->rxPowerDbm === null
            ? OltFeatureResult::unavailable(OltFeature::OPTICAL_SIGNAL, 'OPTICAL_UNAVAILABLE')
            : OltFeatureResult::supported(OltFeature::OPTICAL_SIGNAL, $metrics);
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
        $vlan = (new GetVlanCommand($this->connection))->execute($onu->pon, $onu->onuId);
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
            'DATACOM',
            $onu->getPon(),
            $onu->getId(),
            $onu->getGponId(),
            $onu->getState()
        );
    }

    private function number(mixed $value): ?float
    {
        return is_scalar($value) && preg_match('/-?\d+(?:\.\d+)?/', (string) $value, $matches) === 1
            ? (float) $matches[0]
            : null;
    }

    private function integer(mixed $value): ?int
    {
        $number = $this->number($value);
        return $number === null ? null : (int) round($number);
    }

    private function duration(mixed $value): ?int
    {
        if (!is_scalar($value)
            || preg_match('/(?:(\d+)\s*d)?\s*(\d+):(\d+):(\d+)/i', (string) $value, $matches) !== 1) {
            return null;
        }

        return ((int) ($matches[1] ?? 0) * 86400)
            + ((int) $matches[2] * 3600)
            + ((int) $matches[3] * 60)
            + (int) $matches[4];
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
