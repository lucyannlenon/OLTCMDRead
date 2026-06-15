<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OltInterfaces;

use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;

interface OnuLearnedMacProviderInterface
{
    public function learnedMacs(OnuIdentity $onu): OltFeatureResult;

    public function locateMac(string $macAddress): OltFeatureResult;
}
