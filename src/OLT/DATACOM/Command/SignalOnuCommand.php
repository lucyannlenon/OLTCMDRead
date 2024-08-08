<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;


class SignalOnuCommand extends DetailInfoOnuCommand
{


    protected function getResult(array $data): mixed
    {
        if (!empty($data['signal']))
            return (float)$data['signal'];
        return null;
    }


}