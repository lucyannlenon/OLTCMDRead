<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\Capabilities\OltFeatureState;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$supported = OltFeatureResult::supported(OltFeature::ONU_LIST, []);
expect($supported->state === OltFeatureState::SUPPORTED, 'Empty supported values must be valid.');
expect($supported->toArray()['value'] === [], 'Supported empty arrays must be preserved.');

$unavailable = OltFeatureResult::unavailable(OltFeature::TEMPERATURE, 'ONU_OFFLINE');
expect($unavailable->state === OltFeatureState::UNAVAILABLE, 'Unavailable state was not preserved.');

$unsupported = OltFeatureResult::unsupported(OltFeature::UPTIME, 'COMMAND_NOT_EXPOSED');
expect($unsupported->value === null, 'Unsupported features must not contain a value.');

try {
    new OltFeatureResult(OltFeature::UPTIME, OltFeatureState::UNSUPPORTED, 0);
    throw new RuntimeException('Unsupported values must be rejected.');
} catch (InvalidArgumentException) {
}

try {
    OltFeatureResult::supported('unknown_feature', null);
    throw new RuntimeException('Unknown feature names must be rejected.');
} catch (InvalidArgumentException) {
}

echo "OLT feature contract tests passed.\n";
