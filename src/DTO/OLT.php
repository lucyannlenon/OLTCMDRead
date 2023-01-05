<?php

    namespace LLENON\OltInformation\DTO;

    class OLT
    {
        public $userName;

        public $password;

        public $model;
        public $ip;

        public $port;

        public $nome;

        public $serviceCommunication;

        /**
         * @param $user
         * @param $pw
         * @param $model
         * @param $ip
         * @param $port
         * @param $typeConnection
         * @param $nome
         */
        public function __construct($user, $pw, $model, $ip, $port, $typeConnection, $nome)
        {
            $this->userName = $user;
            $this->password = $pw;
            $this->model = $model;
            $this->ip = $ip;
            $this->port = $port;
            $this->serviceCommunication = $typeConnection;
            $this->nome = $nome;
        }


    }
