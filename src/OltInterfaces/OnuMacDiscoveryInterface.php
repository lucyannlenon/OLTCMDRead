<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OltInterfaces;

use LLENON\OltInformation\Capabilities\OltFeatureResult;
interface OnuMacDiscoveryInterface extends OnuLearnedMacProviderInterface
{
    public function discoverRouterMacs(bool $onlineOnly = true): OltFeatureResult;
}
