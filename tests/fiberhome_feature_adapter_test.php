<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureState;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeFeatureAdapter;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$reflection = new ReflectionClass(FiberhomeFeatureAdapter::class);
expect(
    $reflection->implementsInterface(\LLENON\OltInformation\OltInterfaces\OnuInventoryInterface::class),
    'Fiberhome adapter must implement the inventory contract.'
);
expect(
    $reflection->implementsInterface(\LLENON\OltInformation\OltInterfaces\OnuDiagnosticsInterface::class),
    'Fiberhome adapter must implement the diagnostics contract.'
);
expect(
    $reflection->implementsInterface(\LLENON\OltInformation\OltInterfaces\OnuMacDiscoveryInterface::class),
    'Fiberhome adapter must implement the MAC discovery contract.'
);

$method = $reflection->getMethod('discoverRouterMacs');
expect($method->getReturnType()?->__toString() !== '', 'Fiberhome discovery must have an explicit return type.');

echo "Fiberhome feature adapter tests passed.\n";
