<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureMatrix;
use LLENON\OltInformation\Capabilities\OltFeatureState;
use LLENON\OltInformation\Enum\OltModel;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$matrix = (new OltFeatureMatrix())->build();
foreach ($matrix['rows'] as $row) {
    expect(!in_array('not-tested', $row['states'], true), 'Feature matrix contains a not-tested cell.');
}

$rows = array_column($matrix['rows'], 'states', 'feature');
expect(
    $rows[OltFeature::LEARNED_MACS][OltModel::DATACOM] === OltFeatureState::SUPPORTED,
    'DATACOM learned MAC support is missing.'
);
expect(
    $rows[OltFeature::LEARNED_MACS][OltModel::ZTE] === OltFeatureState::SUPPORTED,
    'ZTE learned MAC support is missing.'
);
expect(
    $rows[OltFeature::LEARNED_MACS][OltModel::FIBERHOME] === OltFeatureState::UNSUPPORTED,
    'Fiberhome TL1 learned MAC limitation must be explicit.'
);
expect(
    $rows[OltFeature::FIRMWARE_DIAGNOSTIC][OltModel::FIBERHOME] === OltFeatureState::UNSUPPORTED,
    'Fiberhome TL1 firmware limitation must be explicit.'
);

echo "OLT feature matrix tests passed.\n";
