<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OltInterfaces;

use LLENON\OltInformation\Capabilities\OltFeatureResult;

interface OnuInventoryInterface
{
    public function listOnus(): OltFeatureResult;

    public function findOnu(string $registrationId): OltFeatureResult;

    public function listUnauthorizedOnus(): OltFeatureResult;
}
