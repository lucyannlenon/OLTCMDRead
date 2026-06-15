<?php

declare(strict_types=1);

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\MissingOltVersionConfigurationException;
use LLENON\OltInformation\Exceptions\UnsupportedOltFirmwareException;
use LLENON\OltInformation\Versioning\OltFirmwareGuard;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$olt = new OLT(
    'user',
    'password',
    OltModel::DATACOM,
    '192.0.2.10',
    22,
    'ssh',
    'test',
    OltCliProfile::DATACOM_DM461X_CLI_V1,
    '9.4.2-042-1-g6453973b4e'
);

$guard = new OltFirmwareGuard();
expect(
    $guard->validateConfiguration($olt)->id === OltCliProfile::DATACOM_DM461X_CLI_V1,
    'DATACOM profile was not resolved.'
);
expect(
    $guard->assertDetectedVersion($olt, "9.4.2-042-1-g6453973b4e Active\n")
        === '9.4.2-042-1-g6453973b4e',
    'Detected DATACOM firmware was not accepted.'
);

try {
    $guard->assertDetectedVersion($olt, "8.6.4-001-1-g5fd3d06d49 Active\n");
    throw new RuntimeException('Connected firmware mismatch must be rejected.');
} catch (UnsupportedOltFirmwareException) {
}

try {
    $guard->validateConfiguration(new OLT(
        'user',
        'password',
        OltModel::ZTE,
        '192.0.2.11',
        22,
        'ssh'
    ));
    throw new RuntimeException('Missing version configuration must be rejected.');
} catch (MissingOltVersionConfigurationException) {
}

$fiberhome = new OLT(
    'user',
    'password',
    OltModel::FIBERHOME,
    '192.0.2.12',
    23,
    'tl1',
    'fiberhome',
    OltCliProfile::FIBERHOME_TL1_CLI_V1,
    null
);
expect(
    $guard->validateConfiguration($fiberhome)->requiresFirmware === false,
    'Fiberhome TL1 must not require an unavailable firmware value.'
);

echo "OLT firmware guard tests passed.\n";
