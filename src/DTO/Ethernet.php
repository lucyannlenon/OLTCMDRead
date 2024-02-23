<?php

    namespace LLENON\OltInformation\DTO;

    class Ethernet
    {

        private mixed $speed;
        private mixed $loopStatus;
        private mixed $speedConfig;
        private mixed $status;

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
        public function setSpeed(mixed $speed): void
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
        public function setLoopStatus(mixed $loopStatus): void
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
        public function setSpeedConfig(mixed $speedConfig): void
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
        public function setStatus(mixed $status): void
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
