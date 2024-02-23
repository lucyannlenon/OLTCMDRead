<?php

namespace LLENON\OltInformation\Adapters;

use DateTime;
use Exception;
use LLENON\OltInformation\Console;
use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\ClienteNotFund;
use LLENON\OltInformation\OltInterfaces;
use Meklis\Network\Console\Telnet;

class OltFiberHomeCmdOLDVERSION implements OltInterfaces\OnuDataInterface
{

    /**
     * @var Console\SSH|Telnet
     */
    private Console\SSH|Telnet $conn;
    private Client $clientModel;

    public function __construct(OLT $oltModel, Client $clientModel)
    {

        $conn = OltModel::getSerive($oltModel->serviceCommunication);
        $conn->setDeviceHelper(OltModel::getHelper($oltModel->model));
        $conn->connect($oltModel->ip, $oltModel->port);
        try {
            $conn->login($oltModel->userName, $oltModel->password);
        } catch (Exception $exception) {
            $conn->login($oltModel->userName, $oltModel->password);
        }

        $conn->exec("EN", true, 'EN');
        $conn->exec($oltModel->password);

        $this->conn = $conn;

        $this->clientModel = $clientModel;
    }

    public function getDadosDoCliente(): Client
    {
        // entra no onu card
        $this->conn->exec("cd gpononu");

        $this->findOnu();

        $this->clear();

        $this->setSinalAndTemperatureOnu();

        $this->clear();

        $this->setDistance();

        $this->clear();

        $this->setUpTime();


        return $this->clientModel;
    }

    /**
     * -----  Physical Address Whitelist Status, ITEM=1 -----
     * SLOT  PON   ONU       TYPE       STATUS   PHY_ID
     * ----- ----- -----  -------------- ------ ------------
     * 16     3    79  AN5506-01-A1   Auth   FHTT04e5c288
     *
     * @throws ClienteNotFund
     */
    private function findOnu(): void
    {

        $outputLines = $this->conn->exec("show whitelist phy_addr select address {$this->clientModel->gponName}");
        $pattern = '/^\s+(\d+)\s+(\d+)\s+(\d+)\s+/m';


// Procurar por correspondências
        preg_match_all($pattern, $outputLines, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            throw new ClienteNotFund("Onu não encontrada!");
        }
        // Iterar sobre as correspondências
        foreach ($matches as $match) {
            if (!empty( $match[1]) && !empty( $match[2]) && !empty( $match[3])) {
                $this->clientModel->slot = $match[1];
                $this->clientModel->pon = $match[2];
                $this->clientModel->onuPosition = $match[3];
            }

        }

    }

    public function __destruct()
    {
        $this->conn->disconnect();
    }

    private function clear()
    {
        $this->conn->exec("clear");
    }

    /**
     * ---------------------------------------
     * TYPE         : 20       (KM)
     * TEMPERATURE  : 37.24    ('C)
     * VOLTAGE      :  3.40    (V)
     * BIAS CURRENT : 16.00    (mA)
     * SEND POWER   :  2.08    (Dbm)
     * RECV POWER   : -22.92   (Dbm)
     * OLT RECV POWER : -23.22 (Dbm)
     */
    public function setSinalAndTemperatureOnu()
    {
        $cmd = "show optic_module slot {$this->clientModel->slot} link {$this->clientModel->pon} onu {$this->clientModel->onuPosition}";
        $input_line = $this->conn->exec($cmd);

        if (preg_match('/TEMPERATURE\s+?:\s+?(?P<temperature>\d+.\d+)/i', $input_line, $output_array)) {
            $this->clientModel->onuTemperatura = $output_array['temperature'];
        }

        if (preg_match('/RECV POWER\s+?:\s+?(?P<sinal>-\d+.\d+)/', $input_line, $output_array)) {
            $this->clientModel->signal = $output_array['sinal'];
            $this->clientModel->status = "Online";
        } else {
            $this->clientModel->status = "Offline";
        }
    }

    /**
     * ONU RTT VALUE = 2583 (m)
     *
     * @return void
     */
    private function setDistance()
    {
        $cmd = "show rtt_value slot {$this->clientModel->slot} link {$this->clientModel->pon} onu {$this->clientModel->onuPosition}";

        $result = $this->conn->exec($cmd);
        if (preg_match('/.*=(?P<distance>.*)/', $result, $output_array)) {
            $this->clientModel->distance = $output_array['distance'];
        }
    }

    /**
     * @throws Exception
     */
    public function setUpTime()
    {
        if ($this->clientModel->status == "Offline") {
            return;
        }
        $cmd = "show onu_last_on_and_off_time slot {$this->clientModel->slot}  link {$this->clientModel->pon} onu {$this->clientModel->onuPosition} ";
        $input_line = $this->conn->exec($cmd);
        if (preg_match('/Last On Time.*=(?P<lastOnTime>.*)./', $input_line, $output_array)) {
            $date = new DateTime('now');
            $init = new DateTime($output_array['lastOnTime']);

            $diff = $date->diff($init);
            $this->clientModel->uptime = $diff->format("%H:%I:%S (Full days: %a)");

        }
    }
}
/**
 * show onu_ver slot 11 link 8
 * show onu_ver slot 11 link 8
 * show auth slot all   link all
 * MONU00c30eb9
 *  show onu-authinfo phy-id
 *
 *  show onu_state slot 11 link 1 onulist 11
 *
 * show optic_module slot 11 link 1 onu 11
 */
