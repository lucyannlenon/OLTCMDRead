<?php

    namespace LLENON\OltInformation\DTO;

    class OLT
    {
        public $userName;

        public $password;

        public $model;
        public $ip;

        public $port;

        public $serviceCommunication;

        /**
         * @param $user
         * @param $pw
         * @param $model
         * @param $ip
         * @param $port
         * @param $typeConnection
         */
        public function __construct($user, $pw, $model, $ip, $port, $typeConnection)
        {
            $this->userName = $user;
            $this->password = $pw;
            $this->model = $model;
            $this->ip = $ip;
            $this->port = $port;
            $this->serviceCommunication = $typeConnection;
        }


    }
