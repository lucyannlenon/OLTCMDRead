<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\DistanceReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\SignalReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TemperatureReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class TemperatureOnuCommand extends SignalOnuCommand
{

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new TemperatureReturnStringParser();
        parent::__construct($connection, $parser);
    }

}