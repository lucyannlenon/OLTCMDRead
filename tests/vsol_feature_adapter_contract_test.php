<?php

declare(strict_types=1);

use LLENON\OltInformation\OLT\VSOL\EPON\VSolEponFeatureAdapter;
use LLENON\OltInformation\OLT\VSOL\GPON\VSolGponFeatureAdapter;
use LLENON\OltInformation\OLT\CDATA\CDataFeatureAdapter;
use LLENON\OltInformation\OLT\DATACOM\DatacomFeatureAdapter;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeFeatureAdapter;
use LLENON\OltInformation\OLT\ZTE\ZteFeatureAdapter;
use LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface;
use LLENON\OltInformation\OltInterfaces\OnuInventoryInterface;
use LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ([
    CDataFeatureAdapter::class,
    DatacomFeatureAdapter::class,
    ZteFeatureAdapter::class,
    FiberhomeFeatureAdapter::class,
    VSolEponFeatureAdapter::class,
    VSolGponFeatureAdapter::class,
] as $adapter) {
    $reflection = new ReflectionClass($adapter);
    expect($reflection->implementsInterface(OnuInventoryInterface::class), "{$adapter} inventory contract missing.");
    expect($reflection->implementsInterface(OnuDiagnosticsInterface::class), "{$adapter} diagnostics contract missing.");
    expect($reflection->implementsInterface(OnuMacDiscoveryInterface::class), "{$adapter} MAC contract missing.");
}

echo "VSOL feature adapter contract tests passed.\n";
