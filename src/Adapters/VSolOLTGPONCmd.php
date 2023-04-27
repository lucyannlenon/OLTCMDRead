<?php

    namespace LLENON\OltInformation\Adapters;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\Ethernet;
    use LLENON\OltInformation\DTO\OLT;
    use LLENON\OltInformation\Enum\OltModel;
    use LLENON\OltInformation\Exceptions\ClienteNotFund;
    use LLENON\OltInformation\OltInterfaces\OnuDataInterface;

    class VSolOLTGPONCmd implements OnuDataInterface
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
            $conn->exec("configure terminal");
            $this->conn = $conn;

        }


        public function getDadosDoCliente(): Client
        {
            $this->setStatusToClient();
            $this->setSignalClient();
            $this->conn->disconnect();
            return $this->clientModel;
        }

        /**
         * @return array
         * @throws ClienteNotFund
         */
        public function findOnu(): array
        {
            $macAddress = strtoupper($this->clientModel->getGponName());
            $cmd = "onu search {$macAddress}";

            $data = $this->conn->exec($cmd);


            $items = preg_grep("/{$macAddress}/", explode("\n", strtoupper($data)));

            // reorganisa os dados para reposicionar o array
            $items = array_values($items);

            if (empty($items)) {
                throw  new ClienteNotFund("Invalid client {$this->clientModel->login}");
            }


            return $this->findOnuInSuchItems($items);
        }

        /**
         * @param array $items
         * @return array
         */
        public function findOnuInSuchItems(array $items): array
        {
            $online = [];
            $pattern = '/pon\s+(\d+)\s+onu\s+(\d+)\s+sn\s+\w+\s+(Online|Offline)/i';

            $matches = [];
            foreach ($items as $item) {

                if (preg_match($pattern, $item, $matches)) {
                    $ponId = $matches[1];
                    $onuId = $matches[2];
                    $onuStatus = $matches[3];

                    if ($onuStatus === "ONLINE") {
                        $online = [
                            'pon' => $ponId,
                            'onu' => $onuId,
                            'status' => $onuStatus
                        ];
                        break;
                    }

                }
            }


            return $online;
        }

        /**
         * @return void
         */
        public function enterTheCorrectInterface(): void
        {
            //entra an interface correta
            $this->conn->exec("interface gpon {$this->clientModel->slot}/{$this->clientModel->pon}");
        }

        /**
         * @param $string
         * @return string
         */
        public function getTemperature($string): string
        {
            $temperature_pattern = '/Temperature:\s?+([\d\.]+)\(C\)/';
            if (preg_match($temperature_pattern, $string, $matches)) {

                return $matches[1];

            }
            return "Temperature not found.";

        }


        /**
         * @return void
         * @throws ClienteNotFund
         */
        private function setStatusToClient()
        {
            $online = $this->findOnu();

            if (empty($online)) {
                $this->clientModel->status = "Offline";
                return;
            }
            $this->clientModel->slot = 0;
            $this->clientModel->pon = $online['pon'];
            $this->clientModel->onuPosition = $online['onu'];
            $this->clientModel->status = $online['status'];
            $this->enterTheCorrectInterface();

            $this->clientModel->distance = $this->getDistance();

            $this->setSignalClient();

            $this->setUptime();

            $this->setEthenetStatus();


        }


        /**
         * @return void
         */
        private function setSignalClient(): void
        {

            $cmd = "show onu {$this->clientModel->onuPosition} optical_info";
            $string = $this->conn->exec($cmd);


            $string = str_replace("\n\e[30C", "", $string);

            $this->clientModel->onuTemperatura = $this->getTemperature($string);

            $this->clientModel->signal = $this->getSinal($string);


        }


        private function getDistance(): string
        {
            $cmd = "show onu {$this->clientModel->onuPosition}  distance";

            $data = $this->conn->exec($cmd);

            $pattern = '/Distance:\s+(\d+)m/';

            if (preg_match($pattern, $data, $matches)) {
                $distance = $matches[1];
                return $distance . "m";
            }

            return "No found.";

        }

        /**
         * @param string $string
         * @return string
         */
        public function getSinal(string $string): string
        {

            $rx_level_pattern = '/Rx optical level:\s?+([\-\d\.]+)\(dBm\)/';
            if (preg_match($rx_level_pattern, $string, $matches)) {
                $rx_level = $matches[1];
                return $rx_level . "dBm";
            }
            return "Rx optical level not found.";

        }

        private function setUptime()
        {
            $cmd = "show onu {$this->clientModel->onuPosition} time-stamp";
            $string = $this->conn->exec($cmd);
            $pattern = '/\d+ \d\d:\d\d:\d\d/';

            if (preg_match_all($pattern, $string, $matches)) {
                $times = $matches[0];
                $aliveTime = end($times);
                $this->clientModel->uptime = $aliveTime;
                return;
            }

            $this->clientModel->uptime = "Alive time not found.";

        }

        private function setEthenetStatus()
        {
            $cmd = "show  onu {$this->clientModel->onuPosition} eth 1";

            $string = $this->conn->exec($cmd);

            $string = str_replace("\n\e[30C", "", $string);

            $patterns = [
                'speed' => 'Speed status:\s+?(\S+)',
                'status' => 'Operate status:\s+?(\S+)',
                'speedConfig' => 'Speed config:\s+?(\S+)',
                'loopStatus' => 'Ethernet loop:\s+?(\S+)'
            ];


            $data = [];
            foreach ($patterns as $k => $pattern) {
                $matches = [];
                preg_match('/' . $pattern . '/', $string, $matches);

                $data[$k] = $matches[1];
            }

            $this->clientModel->ethernet = Ethernet::createFromArray($data);
        }
    }
