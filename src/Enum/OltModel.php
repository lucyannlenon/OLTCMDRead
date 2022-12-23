<?php

    namespace LLENON\OltInformation\Enum;

    use Meklis\Network\Console\AbstractConsole;
    use Meklis\Network\Console\Helpers\Cdata;
    use Meklis\Network\Console\Helpers\DefaultHelper;
    use Meklis\Network\Console\SSH;
    use Meklis\Network\Console\Telnet;
    use LLENON\OltInformation\Helpers\VSolHelper;

    class OltModel
    {
        const CDATA = "CDATA";
        const VSOL = "VSOL";

        /**
         * @param $service
         * @return AbstractConsole
         */
        public static function getSerive($service)
        {
            if ($service == "ssh")
                return new SSH(10, "2.0");
            return new Telnet();
        }

        /**
         * @return DefaultHelper
         */
        public static function getHelper($helper)
        {
            switch ($helper) {
                case self::VSOL:
                    return new VSolHelper();
                case self::CDATA:
                    return new Cdata();
                default:
                    return new DefaultHelper();
            }
        }
    }
