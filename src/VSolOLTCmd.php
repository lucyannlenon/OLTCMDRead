<?php

    namespace LLENON\OltInformation;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;
    use LLENON\OltInformation\Enum\OltModel;
    use LLENON\OltInformation\Exceptions\ClienteNotFund;

    class VSolOLTCmd
    {

        /**
         * @var Client
         */
        private $clientModel;
        /**
         * @var \Meklis\Network\Console\AbstractConsole|\Meklis\Network\Console\SSH|\Meklis\Network\Console\Telnet
         */
        private $conn;


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
            $conn->exec("enable", true, "ord:");
            $conn->exec($oltModel->password);
            echo $conn->exec("configure terminal");
            $this->conn = $conn;
        }


        public function getDadosDoCliente()
        {
            $this->setStatusToClient();
            $this->setSignalClient();
            $this->conn->disconnect();
            return $this->clientModel;
        }

        /**
         * @return string
         */
        private function getMacAddress()
        {
            $arrayMac = explode(":", $this->clientModel->getMacAddress());
            // MODELO ACEITO PELA OLT VSO AAAA:AAAA:AAAA
            return "{$arrayMac[0]}{$arrayMac[1]}:{$arrayMac[2]}{$arrayMac[3]}:{$arrayMac[4]}{$arrayMac[5]}";
        }

        /**
         * @return void
         * @throws ClienteNotFund
         */
        private function setStatusToClient()
        {
            $macAddress = $this->getMacAddress();
            $data = $this->conn->exec("show onu status {$macAddress}");

            $item = preg_grep("/{$this->clientModel->macAddress}/", explode("\n", $data));

            // reorganisa os dados para reposicionar o array
            sort($item);

            if (empty($item)) {
                throw  new ClienteNotFund("Invalid client {$this->clientModel->login}");
            }

            $return = explode("  ", $item[0]);
            $return = array_filter($return);
            $return = array_map(fn($value): string => trim($value), $return);

            // recorganiza o array
            $return = array_values($return);

            $this->setSlotPonOnidToClient($return[0]);
            $this->clientModel->distance = $return[3];
            $this->clientModel->status = $return[1];
            $this->clientModel->uptime = $return[8];

        }

        /**
         * @param $str
         * @return void
         * @see  received text  EPON{slot}/{$pon}:{$onuId}
         */
        private function setSlotPonOnidToClient($str)
        {

            // remove o que não é texto
            $regex = '/([A-Za-z]+)(?P<slot>\d+)\/(?P<pon>\d+):(?P<onuId>\d+)/';
            preg_match($regex, $str, $matches);


            $this->clientModel->slot = $matches['slot'];
            $this->clientModel->pon = $matches['pon'];
            $this->clientModel->onuId = $matches['onuId'];

        }

        /**
         * @return void
         */
        private function setSignalClient(): void
        {
            if (strtolower($this->clientModel->status) != "online") {

                return;
            }

            /**
             * Text aser filtrado
             * ONU-ID      Temperature(C)    Supply Voltage(V)   TX Bias Current(mA)   TX Power(dBm)   RX Power(dBm)\n
             * ------      --------------    -----------------   -------------------   -------------   -------------\n
             * EPON0/2:1   27.45             3.40                14.20                 2.09            -15.97\n
             */
            $data = $this->conn->exec("show onu opm-diag pon {$this->clientModel->pon},{$this->clientModel->onuId}");

            $dataStringToLines = explode("\n", $data);
            $result = $this->getDadosDoSinalEmArray($dataStringToLines);
            $this->clientModel->onuTemperatura = $result[1];
            $this->clientModel->signal = $result[5];
        }

        /**
         * @param $dataStringToLines
         * @return false|string[]
         */
        private function getDadosDoSinalEmArray($dataStringToLines)
        {
            /*
                         * probali result
                         * EPON0/2:29  42.91             3.26                27.54                 2.22            -20.97
                         */
            $regex = "/[A-Za-z]+{$this->clientModel->slot}\/{$this->clientModel->pon}:{$this->clientModel->onuId}/";
            $findString = preg_grep($regex, $dataStringToLines);
            $findString = array_values($findString);
            $result = explode(" ", $findString[0]);
            $result = array_filter($result, fn($value) => $value != "");

            /*
             *  array:6 [
             *        0 => "EPON0/2:29"  // pon
             *        1 => "42.91" // temperatura
             *        2 => "3.26"  // Supply Voltage(V)
             *        3 => "27.54" // TX Bias Current(mA)
             *        4 => "2.22"  // TX Bias Current(mA)
             *        5 => "-21.19"// RX Power(dBm)
             *      ]
             */

            $result = array_values($result);
            return $result;
        }
    }
