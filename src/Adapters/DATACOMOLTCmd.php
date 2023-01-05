<?php

    namespace LLENON\OltInformation\Adapters;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;
    use LLENON\OltInformation\Enum\OltModel;
    use LLENON\OltInformation\Exceptions\ClienteNotFund;
    use LLENON\OltInformation\OltInterfaces\OnuDataInterface;

    class DATACOMOLTCmd implements OnuDataInterface
    {

        /**
         * @var Client
         */
        private $clientModel;
        /**
         * @var \Meklis\Network\Console\AbstractConsole|\Meklis\Network\Console\SSH|\Meklis\Network\Console\Telnet
         */
        private $conn;
        private \DateTime $currentOltTime;


        /**
         * @param OLT $oltModel
         * @param Client $clientModel
         */
        public function __construct(OLT $oltModel, Client $clientModel)
        {
            $this->clientModel = $clientModel;


            $conn = OltModel::getSerive($oltModel->serviceCommunication);
            $conn->setDeviceHelper(OltModel::getHelper($oltModel->model));
            $conn->connect($oltModel->ip, $oltModel->port);
            $conn->login($oltModel->userName, $oltModel->password);
            $this->conn = $conn;
        }

        /**
         * @return Client
         * @throws ClienteNotFund
         */
        public function getDadosDoCliente(): Client
        {


            // pega onde esta a olt
            # -  show interface gpon onu | notab | include

            $this->setDadosDaLocalizacaoDaOnu();

            # comando     show interface gpon 1/1/13 onu 8

            $dadosDaOnu = $this->getDadosDaOnu();

            if (empty($dadosDaOnu))
                throw new ClienteNotFund("Dados da onu não foram encontrados");

            $regexs = [
                'sinal' => 'Rx Optical Power \[dBm\]\s+?:(?P<sinal>.*)',
                'uptime' => 'Uptime\s+?:(?P<uptime>.*)',
                'distance' => 'Distance\s+?:(?P<distance>.*)',
                'status' => 'Operational state\s+?:(?P<status>.*)'
            ];

            foreach ($dadosDaOnu as $line) {
                foreach ($regexs as $k => $regex) {
                    if (preg_match('/' . $regex . '/', $line, $output_array)) {
                        if ($k === 'sinal') {
                            $this->clientModel->signal = trim($output_array[$k]);
                        } elseif ($k === 'uptime') {
                            $this->clientModel->uptime = trim($output_array[$k]);
                        } elseif ($k === 'distance') {
                            $this->clientModel->distance = trim($output_array[$k]);
                        } elseif ($k === 'status') {
                            $this->clientModel->status = trim($output_array[$k]) == 'Up' ? 'Online' : 'Offline';
                        }
                    }
                }
            }


            $this->conn->disconnect();


            return $this->clientModel;
        }

        /**
         * Retorno da olt
         *   Itf     ONU ID   Serial Number   Oper State   Software Download State      Name
         * --------  ------   -------------   ----------   --------------------------   ------------------------------------------------
         * 1/1/1     0        DD15B35EEE17    Up           None
         * 1/1/1     1        MONU00FC4B17    Up           None
         * 1/1/1     2        GPON0083D300    Up           None
         * 1/1/1     4        MONU00BFF2D9    Up           None
         * 1/1/1     5        GPON00953080    Up           None
         * 1/1/1     7        MONU00D39F41    Up           None
         * 1/1/1     9        GPON0085ACD0    Up           None
         * 1/1/1     11       DD15B357EF8D    Up           None
         * 1/1/1     13       MONU005A00E1    Up           None
         * 1/1/1     14       FHTT092B3E94    Up           None
         * 1/1/1     15       DB1946450AA0    Up           None
         * @return void
         * @throws ClienteNotFund
         */
        private function setDadosDaLocalizacaoDaOnu()
        {
            $onuLines = $this->searchOnuInOlt();

            $regexGetDataOlt = '/(?P<placa>\d+)\/(?P<slot>\d+)\/(?P<pon>\d+)\s+?(?P<onuPosition>\d+)/';
            if (empty($onuLines) || !preg_match($regexGetDataOlt, $onuLines[0], $output_array)) {
                throw  new ClienteNotFund("Onu não encontrada!");
            }

            $this->clientModel->slot = "{$output_array['placa']}/{$output_array['slot']}";
            $this->clientModel->pon = $output_array['pon'];
            $this->clientModel->onuPosition = $output_array['onuPosition'];


        }

        public function __destruct()
        {
            $this->conn->disconnect();
        }

        /**
         *  Last updated            : 2017-09-05 23:05:09 UTC+0
         *
         * ID                      : 4
         * Serial Number           : MONU00BFF2D9
         * Password                :
         * Uptime                  : 0 min
         * Last Seen Online        : N/A
         * Vendor ID               : MONU
         * Equipment ID            : MONUD401
         * Name                    :
         * Operational state       : Up
         * Primary status          : Active
         * Distance                : 3 [km]
         * IPv4 mode               : Not configured
         * IPv4 address            :
         * IPv4 default gateway    :
         * IPv4 VLAN               :
         * IPv4 CoS                :
         * Line Profile            : PPPoE2
         * Service Profile         :
         * RG Profile              :
         * RG One Shot Provision   : Not provisioned
         * TR069 ACS Profile       :
         * SNMP                    : Disabled
         * Allocated bandwidth     : 0 fixed, 0 assured+fixed [kbit/s]
         * Upstream-FEC            : Enabled
         * Anti Rogue ONU isolate  : Disabled
         * Version                 : V2.8S
         * Active FW               : V6.0.8P1T8
         * valid, committed
         * Standby FW              : V6.0.8P1T8
         * valid, not committed
         * Software Download State : None
         * Rx Optical Power [dBm]  : -22.36
         * Tx Optical Power [dBm]  : 2.10
         * @return array|false|string[]
         * @throws \Exception
         */
        public function getDadosDaOnu()
        {
            $time = new \DateTime('now');
            $duration = '300';
            $time->add(new \DateInterval('PT' . $duration . 'S'));
            $time = $time->getTimestamp();
            $dadosDaOnu = [];
            do {
                $cmd = "show interface gpon {$this->clientModel->slot}/{$this->clientModel->pon} onu {$this->clientModel->onuPosition}";
                $input_line = $this->conn->exec($cmd, "--|#");
                // timeout
                if ($time < time()) {
                    break;
                }
                if (!empty($input_line))
                    $dadosDaOnu = array_merge($dadosDaOnu, explode("\n", $input_line));

                usleep(200);
            } while (!preg_match('/Tx Optical Power/', $input_line));


            return array_unique($dadosDaOnu);
        }

        /**
         * @return array|false
         * @throws \Exception
         */
        public function searchOnuInOlt()
        {
# -  show interface gpon onu | notab | include
            $cmd = "show interface gpon onu";
            $input_line = $this->conn->exec($cmd, true, '--More--|#');

            $time = new \DateTime('now');
            $duration = '300';
            $time->add(new \DateInterval('PT' . $duration . 'S'));
            $time = $time->getTimestamp();
            while (true) {

                $grep = preg_grep("/{$this->clientModel->gponName}/i", explode("\n", $input_line));

                if ($grep) {
                    $this->conn->exec("\x03", true, '--More--|#');
                    break;
                }

                try {
                    $input_line = $this->conn->exec('\r', true, '--More--');
                } catch (\Exception $e) {
                    $this->conn->exec('\n', true, '#');
                    break;
                }

                if ($time < time()) {
                    break;
                }
            }

            $grep = array_values($grep);
            return $grep;
        }
    }
