<?php

    namespace LLENON\OltInformation\DTO;

    class Client
    {
        public mixed $login;
        public mixed $macAddress;

        public mixed $gponName;

        public $slot;
        public $onuPosition;

        public mixed $pon;
        public mixed $signal;
        public mixed $status;
        public string $distance;
        /**
         * @var mixed|string
         */
        public mixed $uptime;
        /**
         * @var mixed|string
         */
        public mixed $onuTemperatura="None";

        public string $oltNome="" ;
        public Ethernet $ethernet;


        /**
         * @param $login
         * @param $macAddress
         * @param $gponName
         */
        public function __construct($login, $macAddress, $gponName)
        {
            $this->login = $login;
            $this->macAddress = $macAddress;
            $this->gponName = $gponName;
        }

        /**
         * @param string $oltNome
         */
        public function setOltNome(string $oltNome): void
        {
            $this->oltNome = $oltNome;
        }


        /**
         * @return mixed
         */
        public function getLogin()
        {
            return $this->login;
        }

        /**
         * @param mixed $login
         */
        public function setLogin(mixed $login)
        {
            $this->login = $login;
        }

        /**
         * @return mixed
         */
        public function getMacAddress()
        {
            return $this->macAddress;
        }

        /**
         * @param mixed $macAddress
         */
        public function setMacAddress(mixed $macAddress)
        {
            $this->macAddress = $macAddress;
        }

        /**
         * @return mixed
         */
        public function getGponName()
        {
            return $this->gponName;
        }

        /**
         * @param mixed $gponName
         */
        public function setGponName(mixed $gponName)
        {
            $this->gponName = $gponName;
        }

        /**
         * @return mixed
         */
        public function getPon()
        {
            return $this->pon;
        }

        /**
         * @param mixed $pon
         */
        public function setPon(mixed $pon)
        {
            $this->pon = $pon;
        }

        /**
         * @return mixed
         */
        public function getSignal()
        {
            return $this->signal;
        }

        /**
         * @param mixed $signal
         */
        public function setSignal(mixed $signal)
        {
            $this->signal = $signal;
        }

        /**
         * @return mixed
         */
        public function getStatus()
        {
            return $this->status;
        }

        /**
         * @param mixed $status
         */
        public function setStatus(mixed $status)
        {
            $this->status = $status;
        }


    }
