<?php

    namespace LLENON\OltInformation\DTO;

    class Client
    {
        public $login;
        public $macAddress;

        public $gponName;

        public $slot;
        public $onuPosition;

        public $pon;
        public $signal;
        public $status;
        public string $distance;
        /**
         * @var mixed|string
         */
        public $uptime;
        /**
         * @var mixed|string
         */
        public $onuTemperatura="None";

        public $oltNome="" ;
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
        public function setLogin($login)
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
        public function setMacAddress($macAddress)
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
        public function setGponName($gponName)
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
        public function setPon($pon)
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
        public function setSignal($signal)
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
        public function setStatus($status)
        {
            $this->status = $status;
        }


    }
