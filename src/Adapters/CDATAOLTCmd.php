<?php

    namespace LLENON\OltInformation\Adapters;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;
    use LLENON\OltInformation\Enum\OltModel;
    use LLENON\OltInformation\Exceptions\ClienteNotFund;
    use LLENON\OltInformation\OltInterfaces\OnuDataInterface;

    class CDATAOLTCmd implements OnuDataInterface
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
            $conn->connect("{$oltModel->ip}:{$oltModel->port}");
            $conn->login($oltModel->userName, $oltModel->password);
            $conn->exec("enable");
            $conn->exec("config");
            $this->conn = $conn;
        }

        /**
         * @return Client
         * @throws ClienteNotFund
         */
        public function getDadosDoCliente():Client
        {

            $this->pesquisaDadosIniciaisDoCliente();

            $this->setUpTimeOlt();

            $comandoEntrarNaInterface = "interface epon {$this->clientModel->slot}";
            $this->conn->exec($comandoEntrarNaInterface);

            $this->setDistaceAndUptimeInClient();
            $this->setSinalCliente();

            $this->conn->disconnect();
            return $this->clientModel;
        }

        /**
         * @return void
         * @throws ClienteNotFund
         */
        private function pesquisaDadosIniciaisDoCliente(): void
        {
            $cmd = "show ont info by-mac {$this->clientModel->getMacAddress()}";
            /*
                  OLT - MANTIMENTO(config)# show ont info by-mac C4:70:0B:89:51:60
-----------------------------------------------------------------------------
  F/S  P  ONT MAC               Control   Run      Config   Match     Desc
          ID                    flag      state    state    state
 ----------------------------------------------------------------------------
  0/0  2  3   C4:70:0B:89:51:60 active    online   success  match
-----------------------------------------------------------------------------
             */
            $input_lines = $this->conn->exec($cmd);
            $retorno = preg_grep("/{$this->clientModel->getMacAddress()}/", explode("\n", $input_lines));

            $retorno = array_values($retorno);
            if (empty($retorno)) {
                throw new ClienteNotFund("Cliente não encontrado na olt");
            }

            $data = explode(" ", $retorno[0]);

            $return = array_filter($data);
            $return = array_map(fn($value): string => trim($value), $return);

            // recorganiza o array
            $return = array_values($return);

            // data pos filter
            /*
             *  [
              0 => "0/0"
              1 => "2"
              2 => "3"
              3 => "C4:70:0B:89:51:60"
              4 => "active"
              5 => "online"
              6 => "success"
              7 => "match"
            ]
             */


            $this->clientModel->slot = $return[0];
            $this->clientModel->pon = $return[1];
            $this->clientModel->onuPosition = $return[2];
            $this->clientModel->status = $return[5];
        }

        /**
         * Retnorno  da olt
         * -----------------------------------------------------------------------------
         * Frame/Slot           : 0/0
         * Port                 : 2
         * ONT-ID               : 3
         * Control flag         : active
         * Run state            : online
         * Config state         : success
         * Match state          : match
         * LLID                 : 3
         * ONT distance         : 1972m
         * Discovery mode       : always
         * Auth mode            : mac-auth
         * MAC                  : C4:70:0B:89:51:60
         * Last up time         : 2000-05-22 00:30:53
         * Last down time       : 2000-05-22 00:29:28
         * Description          :
         * Last dying gasp time : 2000-05-22 00:29:26
         * Last down cause      : dying-gasp
         * ----------------------------------------------------------------------------
         * @return void
         */
        private function setDistaceAndUptimeInClient(): void
        {
            $comandoPegarInformacoesOnu = "show ont info {$this->clientModel->pon} {$this->clientModel->onuPosition}";
            $input_lines = $this->conn->exec($comandoPegarInformacoesOnu, true, '\)--');

            $grep = preg_grep('/ONT distance|Last up time/', explode("\n", $input_lines));
            $grep = array_values($grep);

            if (count($grep) > 0) {
                foreach ($grep as $line) {
                    if (preg_match('/ONT distance/', $line)) {
                        $this->clientModel->distance = preg_replace('/\D/', '', $grep[0]);
                        continue;
                    } elseif (preg_match('/Last up time[\s]+?: (?P<bootTime>.*)/', $line, $output_array)) {
                        $this->setUptimeOnu($output_array['bootTime']);
                    }
                }
            }

            $this->conn->exec("q");
        }

        /**
         *Rentorno da olt
         * -----------------------------------------------------------------------------
         * Frame/Slot                 : 0/0
         * Port                       : 2
         * ONT-ID                     : 3
         * ONT-MAC                    : C4:70:0B:89:51:60
         * Voltage(V)                 : 3.30
         * Tx optical power(dBm)      : 1.97
         * Rx optical power(dBm)      : -21.94
         * Laser bias current(mA)     : 15.16
         * Temperature(C)             : 50.73
         * -----------------------------------------------------------------------------
         * @return void
         */
        private function setSinalCliente(): void
        {


            $comandoPegarInformacoesOnu = "show ont optical-info {$this->clientModel->pon} {$this->clientModel->onuPosition}";
            $input_lines = $this->conn->exec($comandoPegarInformacoesOnu);

            $grep = preg_grep('/Rx optical power|Temperature/', explode("\n", $input_lines));
            $grep = array_values($grep);
            if (count($grep) > 0) {
                foreach ($grep as $line) {
                    $data = preg_replace('/[^\d.-]/', '', $line);
                    if (preg_match('/Temperature/', $line)) {
                        $this->clientModel->onuTemperatura = $data;
                        continue;

                    }
                    $this->clientModel->signal = $data;

                }
            }
        }


        private function setUpTimeOlt()
        {
            $input_line = $this->conn->exec("show time");
            $botDate = new \DateTime(trim($input_line));

            $this->currentOltTime = $botDate;

        }

        private function setUptimeOnu(string $bootTime)
        {
            $date = new \DateTime(trim($bootTime));

            if (empty($this->currentOltTime))
                return;
            $diff = $this->currentOltTime->diff($date);
            $this->clientModel->uptime = $diff->format("%H:%I:%S (Full days: %a)");
        }


    }
