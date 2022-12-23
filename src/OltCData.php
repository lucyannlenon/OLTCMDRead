<?php

    namespace LLENON\OltInformation;

    use Meklis\Network\Console\AbstractConsole;

    class OltCData
    {
        /**
         * @var AbstractConsole
         */
        private $console;

        /**
         * @param AbstractConsole $console
         */
        public function __construct(AbstractConsole $console)
        {
            $this->console = $console;
        }

        public function getSignalByMacAddress($macAddress)
        {

        }


    }
