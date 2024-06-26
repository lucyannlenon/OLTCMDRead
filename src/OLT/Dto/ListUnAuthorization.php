<?php

namespace LLENON\OltInformation\OLT\Dto;

class ListUnAuthorization
{
    /** @var Onu[] */
    private array $onus = [];

    public function addOnu(Onu $onu):void
    {
        $this->onus[]= $onu ;
    }
    public function clear():void
    {
        $this->onus=[] ;
    }

    public function getOnus(): array
    {
        return $this->onus;
    }


}