<?php

namespace LLENON\OltInformation\Helpers\Ip;

class RandomIpLocalAddress
{
    public static function getAddress():string
    {
        $ipStart = ip2long('127.0.0.2');
        $ipEnd = ip2long('127.255.255.255');

        return long2ip(rand($ipStart, $ipEnd));
    }

}