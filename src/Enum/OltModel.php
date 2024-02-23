<?php

    namespace LLENON\OltInformation\Enum;

    use Exception;
    use LLENON\OltInformation\Adapters\CDATAOLTCmd;
    use LLENON\OltInformation\Adapters\DATACOMOLTCmd;
    use LLENON\OltInformation\Adapters\OltFiberHomeCmd;
    use LLENON\OltInformation\Adapters\OltFiberHomeCmdOLDVERSION;
    use LLENON\OltInformation\Adapters\VSolOLTCmd;
    use LLENON\OltInformation\Adapters\VSolOLTGPONCmd;
    use LLENON\OltInformation\Console\SSH;
    use LLENON\OltInformation\Helpers\DATACOMHelper;
    use LLENON\OltInformation\Helpers\FiberHomeHelper;
    use Meklis\Network\Console\Helpers\Cdata;
    use Meklis\Network\Console\Helpers\DefaultHelper;
    use Meklis\Network\Console\Telnet;
    use LLENON\OltInformation\Helpers\VSolHelper;

    class OltModel
    {
        const CDATA = "CDATA";
        const VSOL = "VSOL";
        const VSOLGPON = "VSOLGPON";
        const DATACOM = 'DATACOM';
        const FIBERHOMEOLDVERSION = "FIBERHOMEOLDVERSION";
        const FIBERHOME = "FIBERHOME";

        const ADAPTERS = [
            self::CDATA => CDATAOLTCmd::class,
            self::VSOL => VSolOLTCmd::class,
            self::VSOLGPON => VSolOLTGPONCmd::class,
            self::DATACOM => DATACOMOLTCmd::class,
            self::FIBERHOME => OltFiberHomeCmd::class,
            self::FIBERHOMEOLDVERSION => OltFiberHomeCmdOLDVERSION::class
        ];

        /**
         * @param $service
         * @return SSH|Telnet
         * @throws Exception
         */
        public static function getSerive($service)
        {
            if ($service == "ssh")
                return new SSH(10, "2.0");

            return new Telnet(50);
        }

        /**
         * @return DefaultHelper
         */
        public static function getHelper($helper)
        {
            switch ($helper) {
                case self::VSOL:
                case self::VSOLGPON :
                    return new VSolHelper();
                case self::CDATA:
                    return new Cdata();
                case self::DATACOM:
                    return new DATACOMHelper();
                case self::FIBERHOMEOLDVERSION:
                case self::FIBERHOME:
                    return new FiberHomeHelper();
                default:
                    return new DefaultHelper();
            }
        }
    }
