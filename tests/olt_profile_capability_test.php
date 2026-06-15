<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltCapabilityRegistry;
use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$profiles = (new OltCliProfileRegistry())->all();
$epon = array_values(array_filter(
    $profiles,
    static fn ($profile): bool => $profile->id === OltCliProfile::VSOL_EPON_CLI_V1
))[0] ?? null;

expect($epon !== null, 'VSOL EPON profile must exist.');
expect($epon->transport === 'telnet', 'VSOL EPON transport must come from the profile.');
expect($epon->defaultPort === 23, 'VSOL EPON port must come from the profile.');
expect(in_array(OltFeature::ROUTER_MAC_DISCOVERY, $epon->features, true), 'VSOL EPON MAC discovery missing.');

$catalog = (new OltCapabilityRegistry())->catalog();
$vsol = array_values(array_filter(
    $catalog['models'],
    static fn (array $model): bool => $model['model'] === OltModel::VSOL
))[0] ?? null;

expect(is_array($vsol), 'VSOL model must exist in the capability catalog.');
expect(in_array(OltFeature::ONU_LIST, $vsol['features'], true), 'Model feature union is incomplete.');
expect($vsol['cliProfiles'][0]['defaultTransport'] === 'telnet', 'Profile transport was not exposed.');

$models = array_column($catalog['models'], null, 'model');
expect(
    $models[OltModel::CDATA]['firmwares'] === ['V1.6.5_250321'],
    'CDATA homologated firmware is missing.'
);
expect(
    $models[OltModel::ZTE]['firmwares'] === ['V1.2.2'],
    'ZTE homologated firmware is missing.'
);
expect(
    count($models[OltModel::DATACOM]['firmwares']) === 3,
    'DATACOM homologated firmware list is incomplete.'
);
expect(
    $models[OltModel::FIBERHOME]['firmwareMode'] === 'unavailable',
    'Fiberhome TL1 firmware mode must be unavailable.'
);

echo "OLT profile capability tests passed.\n";
