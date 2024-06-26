<?php

namespace LLENON\OltInformation\DTO;

class ONU
{
    private string $name ;
    private string $signal ;
    private string $distance ;
    private StatusLinkEnum $status ;
    private string $MAC ;

    private  string $pon;

    private string $onuType;


    public function __construct(
        private readonly string $id,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSignal(): string
    {
        return $this->signal;
    }

    public function getStatus(): StatusLinkEnum
    {
        return $this->status;
    }

    public function setStatus(StatusLinkEnum $status): void
    {
        $this->status = $status;
    }


    public function setSignal(string $signal): void
    {
        $this->signal = $signal;
    }

    public function getDistance(): string
    {
        return $this->distance;
    }

    public function setDistance(string $distance): void
    {
        $this->distance = $distance;
    }



    public function getMAC(): string
    {
        return $this->MAC;
    }

    public function getPon(): string
    {
        return $this->pon;
    }

    public function setPon(string $pon): void
    {
        $this->pon = $pon;
    }


    public function setMAC(string $MAC): void
    {
        $this->MAC = $MAC;
    }

    public function getOnuType(): string
    {
        return $this->onuType;
    }

    public function setOnuType(string $onuType): void
    {
        $this->onuType = $onuType;
    }


}