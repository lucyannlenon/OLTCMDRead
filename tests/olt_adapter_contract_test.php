<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;
use LLENON\OltInformation\OLT\Utils\Discovery\OnuRouterMacDiscovery;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuLearnedMacProviderInterface;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$online = new OnuIdentity('TEST', '1', '1', 'AA:BB:CC:DD:EE:01', 'online');
$offline = new OnuIdentity('TEST', '1', '2', 'AA:BB:CC:DD:EE:02', 'offline');

$inventory = new class($online, $offline) implements OnuInventoryInterface {
    public function __construct(
        private readonly OnuIdentity $online,
        private readonly OnuIdentity $offline
    ) {
    }

    public function listOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(OltFeature::ONU_LIST, [$this->online, $this->offline]);
    }

    public function findOnu(string $registrationId): OltFeatureResult
    {
        return OltFeatureResult::unavailable(OltFeature::ONU_LOOKUP, 'NOT_FOUND');
    }

    public function listUnauthorizedOnus(): OltFeatureResult
    {
        return OltFeatureResult::supported(OltFeature::UNAUTHORIZED_ONUS, []);
    }
};

$macProvider = new class implements OnuLearnedMacProviderInterface {
    public int $queries = 0;

    public function learnedMacs(OnuIdentity $onu): OltFeatureResult
    {
        $this->queries++;
        return OltFeatureResult::supported(OltFeature::LEARNED_MACS, [
            new LearnedMacAddress($onu->registrationId, '100', $onu->pon, $onu->onuId, 'dynamic'),
            new LearnedMacAddress('11:22:33:44:55:66', '100', $onu->pon, $onu->onuId, 'dynamic'),
        ]);
    }

    public function locateMac(string $macAddress): OltFeatureResult
    {
        return OltFeatureResult::unavailable(OltFeature::REVERSE_MAC_LOOKUP, 'NOT_FOUND');
    }
};

$result = (new OnuRouterMacDiscovery($inventory, $macProvider))->discover();
expect($result->feature === OltFeature::ROUTER_MAC_DISCOVERY, 'Unexpected discovery feature.');
expect(count($result->value) === 1, 'Offline ONUs must be skipped by default.');
expect($macProvider->queries === 1, 'Only online ONUs must be queried.');
expect(
    count($result->value[0]['macAddresses']) === 1
    && $result->value[0]['macAddresses'][0]->macAddress === '11:22:33:44:55:66',
    'The ONU registration MAC must be removed.'
);

echo "OLT adapter contract tests passed.\n";
