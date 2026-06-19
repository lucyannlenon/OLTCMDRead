<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Fiberhome;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuEthernetStatus;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Dto\OnuOperationalStatus;
use LLENON\OltInformation\OLT\Dto\OnuOpticalMetrics;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\DistanceOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\EtherStateOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListAllOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListOnuMacAddressCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListUnAuthorizationOnu;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\SignalOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\TemperatureOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\VlanOnuCommand;
use LLENON\OltInformation\OLT\Utils\Feature\AbstractOltFeatureProvider;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

final readonly class FiberhomeFeatureAdapter extends AbstractOltFeatureProvider implements
    OnuInventoryInterface,
    OnuDiagnosticsInterface,
    OnuMacDiscoveryInterface
{
    private ListAllOnuCommand $listOnu;
    private ListUnAuthorizationOnu $listUnauthorized;
    private DistanceOnuCommand $distance;
    private SignalOnuCommand $signal;
    private TemperatureOnuCommand $temperature;
    private EtherStateOnuCommand $ethernet;
    private VlanOnuCommand $vlanCommand;

    public function __construct(
        private FiberhomeConnection $connection
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
        ]);
        $this->listOnu = new ListAllOnuCommand($connection);
        $this->listUnauthorized = new ListUnAuthorizationOnu($connection);
        $this->distance = new DistanceOnuCommand($connection);
        $this->signal = new SignalOnuCommand($connection);
        $this->temperature = new TemperatureOnuCommand($connection);
        $this->ethernet = new EtherStateOnuCommand($connection);
        $this->vlanCommand = new VlanOnuCommand($connection);
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
        return OltFeatureResult::supported(
            OltFeature::ONU_STATUS,
            new OnuOperationalStatus(
                $onu->state,
                $this->distance->execute($onu->pon, $onu->registrationId)
            )
        );
    }

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::OPTICAL_SIGNAL,
            new OnuOpticalMetrics(
                $this->signal->execute($onu->pon, $onu->registrationId),
                temperatureCelsius: $this->temperature->execute($onu->pon, $onu->registrationId)
            )
        );
    }

    public function ethernetStatus(OnuIdentity $onu, int $port = 1): OltFeatureResult
    {
        $entry = $this->ethernet->execute($onu->pon, $onu->registrationId)[$port - 1] ?? null;
        if ($entry === null) {
            return OltFeatureResult::unavailable(OltFeature::ETHERNET_STATE, 'ETHERNET_PORT_UNAVAILABLE');
        }

        return OltFeatureResult::supported(
            OltFeature::ETHERNET_STATE,
            new OnuEthernetStatus('up', $this->parseSpeed($entry->speed))
        );
    }

    public function vlan(OnuIdentity $onu): OltFeatureResult
    {
        $vlan = $this->vlanCommand->execute($onu->pon, $onu->registrationId);
        return $vlan > 0
            ? OltFeatureResult::supported(OltFeature::VLAN, $vlan)
            : OltFeatureResult::unavailable(OltFeature::VLAN, 'VLAN_UNAVAILABLE');
    }

    public function learnedMacs(OnuIdentity $onu): OltFeatureResult
    {
        return OltFeatureResult::supported(
            OltFeature::LEARNED_MACS,
            (new ListOnuMacAddressCommand($this->connection))->execute($onu->pon, $onu->registrationId)
        );
    }

    public function locateMac(string $macAddress): OltFeatureResult
    {
        return $this->unsupported(OltFeature::REVERSE_MAC_LOOKUP);
    }

    public function discoverRouterMacs(bool $onlineOnly = true): OltFeatureResult
    {
        return $this->unsupported(OltFeature::ROUTER_MAC_DISCOVERY);
    }

    private function mapOnu(Onu $onu): OnuIdentity
    {
        return new OnuIdentity(
            'FIBERHOME',
            $onu->getPon(),
            $onu->getId(),
            $onu->getGponId(),
            $onu->getState()
        );
    }

    private function parseSpeed(string $speed): ?int
    {
        if (preg_match('/(\d+)/', $speed, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
