<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;


class DistanceOnuCommand extends DetailInfoOnuCommand
{

    /**
     * @param array $data
     * @return mixed
     */
    public function getResult(array $data): mixed
    {
        return empty($data['distance']) ? null : $data['distance'];
    }


}