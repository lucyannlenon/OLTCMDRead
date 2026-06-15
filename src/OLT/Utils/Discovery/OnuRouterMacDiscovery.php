<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Utils\Discovery;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\Capabilities\OltFeatureState;
use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuLearnedMacProviderInterface;

final readonly class OnuRouterMacDiscovery
{
    public function __construct(
        private OnuInventoryInterface $inventory,
        private OnuLearnedMacProviderInterface $macProvider
    ) {
    }

    public function discover(bool $onlineOnly = true): OltFeatureResult
    {
        $inventory = $this->inventory->listOnus();
        if ($inventory->state !== OltFeatureState::SUPPORTED) {
            return new OltFeatureResult(
                OltFeature::ROUTER_MAC_DISCOVERY,
                $inventory->state,
                null,
                $inventory->reasonCode
            );
        }

        if (!is_array($inventory->value)) {
            throw new \UnexpectedValueException('ONU inventory must return an array.');
        }

        $results = [];
        foreach ($inventory->value as $onu) {
            if (!$onu instanceof OnuIdentity) {
                throw new \UnexpectedValueException('ONU inventory contains an invalid value.');
            }

            if ($onlineOnly && !$this->isOnline($onu->state)) {
                continue;
            }

            $learned = $this->macProvider->learnedMacs($onu);
            if ($learned->state === OltFeatureState::UNSUPPORTED) {
                return OltFeatureResult::unsupported(
                    OltFeature::ROUTER_MAC_DISCOVERY,
                    $learned->reasonCode ?? 'LEARNED_MACS_UNSUPPORTED'
                );
            }

            if ($learned->state === OltFeatureState::UNAVAILABLE) {
                $results[] = ['onu' => $onu, 'macAddresses' => []];
                continue;
            }

            if (!is_array($learned->value)) {
                throw new \UnexpectedValueException('Learned MAC lookup must return an array.');
            }

            $registrationMac = $this->normalizeRegistrationMac($onu->registrationId);
            $macs = array_values(array_filter(
                $learned->value,
                static function (mixed $entry) use ($registrationMac): bool {
                    if (!$entry instanceof LearnedMacAddress) {
                        throw new \UnexpectedValueException('Learned MAC lookup contains an invalid value.');
                    }

                    return $registrationMac === null || $entry->macAddress !== $registrationMac;
                }
            ));

            $results[] = ['onu' => $onu, 'macAddresses' => $macs];
        }

        return OltFeatureResult::supported(OltFeature::ROUTER_MAC_DISCOVERY, $results);
    }

    private function isOnline(string $state): bool
    {
        return in_array(strtolower(trim($state)), ['online', 'working', 'up'], true);
    }

    private function normalizeRegistrationMac(string $registrationId): ?string
    {
        try {
            return MacLocation::normalizeMacAddress($registrationId);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
