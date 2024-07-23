<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use Exception;
use LLENON\OltInformation\OLT\Dto\Onu;

class NextIdCommand extends ListOnuCommand
{


    /**
     * @throws Exception
     */
    public function execute(string $pon): int
    {
        /** @var Onu[] $ret */
        $ret = parent::execute($pon);
        $i = 1;
        foreach ($ret as $item) {
            if(empty($item)){
                continue ;
            }
            if (!empty($item) && $item->getId() != $i) {

                return $i;
            }
            $i++;
        }
        if ($i > 128) {
            throw new Exception("PON has reached its maximum capacity of 128 ONUs.");
        }
        return $i;
    }


}