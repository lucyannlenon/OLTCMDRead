<?php

    namespace LLENON\OltInformation\DTO;

    class Ethernet
    {

        private $speed;
        private $loopStatus;
        private $speedConfig;
        private $status;

        /**
         * @param $speed
         * @param $loopStatus
         * @param $speedConfig
         * @param $status
         */
        public function __construct($speed, $loopStatus, $speedConfig, $status)
        {
            $this->speed = $speed;
            $this->loopStatus = $loopStatus;
            $this->speedConfig = $speedConfig;
            $this->status = $status;
        }

        /**
         * @return mixed
         */
        public function getSpeed()
        {
            return $this->speed;
        }

        /**
         * @param mixed $speed
         */
        public function setSpeed($speed): void
        {
            $this->speed = $speed;
        }

        /**
         * @return mixed
         */
        public function getLoopStatus()
        {
            return $this->loopStatus;
        }

        /**
         * @param mixed $loopStatus
         */
        public function setLoopStatus($loopStatus): void
        {
            $this->loopStatus = $loopStatus;
        }

        /**
         * @return mixed
         */
        public function getSpeedConfig()
        {
            return $this->speedConfig;
        }

        /**
         * @param mixed $speedConfig
         */
        public function setSpeedConfig($speedConfig): void
        {
            $this->speedConfig = $speedConfig;
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
        public function setStatus($status): void
        {
            $this->status = $status;
        }

        public static function createFromArray(array $data): Ethernet
        {
            return new self(
                $data['speed'],
                $data['loopStatus'],
                $data['speedConfig'],
                $data['status']
            );
        }


    }
