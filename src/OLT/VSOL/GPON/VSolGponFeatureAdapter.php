<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\VSOL\GPON;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuEthernetStatus;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Dto\OnuOperationalStatus;
use LLENON\OltInformation\OLT\Dto\OnuOpticalMetrics;
use LLENON\OltInformation\OLT\Utils\Discovery\OnuRouterMacDiscovery;
use LLENON\OltInformation\OLT\Utils\Feature\AbstractOltFeatureProvider;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\EthernetStatusCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\FindOnuCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\LocateMacAddressCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\OnuStatusCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\OpticalInfoCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

final readonly class VSolGponFeatureAdapter extends AbstractOltFeatureProvider implements
    OnuInventoryInterface,
    OnuDiagnosticsInterface,
    OnuMacDiscoveryInterface
{
    public function __construct(
        private VSolGponConnectionInterface $connection
    ) {
        parent::__construct([
            OltFeature::ONU_LIST,
            OltFeature::ONU_LOOKUP,
            OltFeature::ONU_STATUS,
            OltFeature::OPTICAL_SIGNAL,
            OltFeature::TEMPERATURE,
            OltFeature::DISTANCE,
            OltFeature::UPTIME,
            OltFeature::ETHERNET_STATE,
            OltFeature::ETHERNET_SPEED,
            OltFeature::LEARNED_MACS,
            OltFeature::REVERSE_MAC_LOOKUP,
            OltFeature::ROUTER_MAC_DISCOVERY,
        ]);
    }

    public function listOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::ONU_LIST,
            array_map($this->mapOnu(...), (new ListOnuCommand($this->connection))->execute())
        );
    }

    public function findOnu(string $registrationId): OltFeatureResult
    {
        $onu = (new FindOnuCommand($this->connection))->execute($registrationId);
        return $onu === null
            ? OltFeatureResult::unavailable(OltFeature::ONU_LOOKUP, 'ONU_NOT_FOUND')
            : OltFeatureResult::supported(OltFeature::ONU_LOOKUP, $this->mapOnu($onu));
    }

    public function listUnauthorizedOnus(): OltFeatureResult
    {
        return $this->unsupported(OltFeature::UNAUTHORIZED_ONUS);
    }

    public function status(OnuIdentity $onu): OltFeatureResult
    {
        $status = (new OnuStatusCommand($this->connection))->execute((int) $onu->pon, (int) $onu->onuId);
        return $status === null
            ? OltFeatureResult::unavailable(OltFeature::ONU_STATUS, 'STATUS_UNAVAILABLE')
            : OltFeatureResult::supported(
                OltFeature::ONU_STATUS,
                new OnuOperationalStatus($status->phaseState)
            );
    }

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult
    {
        $info = (new OpticalInfoCommand($this->connection))->execute((int) $onu->pon, (int) $onu->onuId);
        return $info === null
            ? OltFeatureResult::unavailable(OltFeature::OPTICAL_SIGNAL, 'OPTICAL_UNAVAILABLE')
            : OltFeatureResult::supported(
                OltFeature::OPTICAL_SIGNAL,
                new OnuOpticalMetrics(
                    $this->number($info->rxOpticalLevel),
                    $this->number($info->txOpticalLevel),
                    $this->number($info->temperature),
                    $this->number($info->voltage),
                    $this->number($info->laserBiasCurrent)
                )
            );
    }

    public function ethernetStatus(OnuIdentity $onu, int $port = 1): OltFeatureResult
    {
        $status = (new EthernetStatusCommand($this->connection))
            ->execute((int) $onu->pon, (int) $onu->onuId, $port);
        return $status === null
            ? OltFeatureResult::unavailable(OltFeature::ETHERNET_STATE, 'ETHERNET_UNAVAILABLE')
            : OltFeatureResult::supported(
                OltFeature::ETHERNET_STATE,
                new OnuEthernetStatus(
                    $status->status,
                    $this->integer($status->speed),
                    $status->speedConfig,
                    $status->loopStatus
                )
            );
    }

    public function vlan(OnuIdentity $onu): OltFeatureResult
    {
        return $this->unsupported(OltFeature::VLAN);
    }

    public function learnedMacs(OnuIdentity $onu): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::LEARNED_MACS,
            (new ListOnuMacAddressCommand($this->connection))
                ->execute((int) $onu->pon, (int) $onu->onuId)
        );
    }

    public function locateMac(string $macAddress): OltFeatureResult
    {
        $entry = (new LocateMacAddressCommand($this->connection))->execute($macAddress);
        if ($entry === null) {
            return OltFeatureResult::unavailable(OltFeature::REVERSE_MAC_LOOKUP, 'MAC_NOT_FOUND');
        }

        if (preg_match('/(\d+)\/(\d+)$/', $entry->port, $matches) !== 1) {
            return OltFeatureResult::unavailable(OltFeature::REVERSE_MAC_LOOKUP, 'ONU_LOCATION_UNAVAILABLE');
        }

        return OltFeatureResult::supported(
            OltFeature::REVERSE_MAC_LOOKUP,
            new MacLocation(
                $entry->macAddress,
                $matches[1],
                $matches[2],
                $entry->vlan,
                $entry->type,
                $entry->port
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
            'VSOLGPON',
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
        return preg_match('/\d+/', $value, $matches) === 1 ? (int) $matches[0] : null;
    }
}
