<?php

namespace LLENON\OltInformation\OLT\Dto;

readonly class OnuEthernet
{

    public function __construct(
        public string $name ,
        public string $speed
    )
    {
    }
}