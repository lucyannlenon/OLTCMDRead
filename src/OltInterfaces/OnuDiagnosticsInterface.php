<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OltInterfaces;

use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;

interface OnuDiagnosticsInterface
{
    public function status(OnuIdentity $onu): OltFeatureResult;

    public function opticalMetrics(OnuIdentity $onu): OltFeatureResult;

    public function ethernetStatus(OnuIdentity $onu, int $port = 1): OltFeatureResult;

    public function vlan(OnuIdentity $onu): OltFeatureResult;
}
