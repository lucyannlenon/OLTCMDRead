<?php

namespace LLENON\OltInformation\OLT\Dto;

class Onu
{
    private string $pon ;
    private string $model ;
    private string $gponId;
    private string $offlineTimes;
    private string $state;
    private string $id;
    private string $vlan;
    private string $username;
    private string $password;

    public function getPon(): string
    {
        return $this->pon;
    }

    public function setPon(string $pon): Onu
    {
        $this->pon = $pon;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): Onu
    {
        $this->model = $model;
        return $this;
    }

    public function getGponId(): string
    {
        return $this->gponId;
    }

    public function setGponId(string $gponId): Onu
    {
        $this->gponId = $gponId;
        return $this;
    }

    public function getOfflineTimes(): string
    {
        return $this->offlineTimes;
    }

    public function setOfflineTimes(string $offlineTimes): Onu
    {
        $this->offlineTimes = $offlineTimes;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): Onu
    {
        $this->state = $state;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Onu
    {
        $this->id = $id;
        return $this;
    }

    public function getVlan(): string
    {
        return $this->vlan;
    }

    public function setVlan(string $vlan): Onu
    {
        $this->vlan = $vlan;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): Onu
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): Onu
    {
        $this->password = $password;
        return $this;
    }





}