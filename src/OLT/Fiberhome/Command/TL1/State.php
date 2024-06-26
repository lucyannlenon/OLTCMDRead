<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\DTO\StatusLinkEnum;

class State extends AbstractTL1Command
{


    public function exec(mixed $params = null): array
    {
        $onu = $params['onu'];

        if (!$onu instanceof ONU) {
            throw new \Exception("params with key onu need instance of " . ONU::class);
        }

        $cmd = "LST-ONUCFG::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::;";

        $retCommand = $this->conn->exec($cmd);
        $data = $this->extractInformation($retCommand);
        if (empty($data)) {
            throw new \Exception('Signal Not found');
        }
        $onu->setStatus(StatusLinkEnum::getStatus($data["state"]));
        $onu->setDistance($data["distance"]);
        return $data;

    }

    private function extractInformation($string): array
    {
        $lines = explode("\n", $string);
        $unRegOnuInfo = [];

        foreach ($lines as $line) {
            if (str_contains($line, '-----') || str_contains($line, 'ONUID')) {
                continue;
            }
            if (!empty(trim($line))) {
                $data = preg_split('/\t/', $line);
                if (count($data) >= 3) {
                    $unRegOnuInfo['state'] = "{$data[1]}";
                    $unRegOnuInfo['distance'] = "{$data[4]}";
                }
            }
        }

        return $unRegOnuInfo;
    }

}