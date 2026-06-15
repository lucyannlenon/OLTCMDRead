<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OltInterfaces;

use LLENON\OltInformation\Capabilities\OltFeatureResult;

interface OltFeatureProviderInterface
{
    /** @return list<string> */
    public function supportedFeatures(): array;

    public function supports(string $feature): bool;

    public function unsupported(string $feature): OltFeatureResult;
}
