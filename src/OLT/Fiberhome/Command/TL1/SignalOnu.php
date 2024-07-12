<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;

class SignalOnu extends AbstractTL1Command
{


    public function exec(mixed $params = null): array
    {
        $onu = $params['onu'];

        if (!$onu instanceof ONU) {
            throw new \Exception("params with key onu need instance of " . ONU::class);
        }
        dump(date('Y-m-d H:i:s'));
        $cmd = "LST-OMDDM::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::;";
        dump($cmd);
        $retCommand = $this->conn->exec($cmd);
        $data = $this->extractInformation($retCommand);
        if (empty($data)) {
            throw new \Exception('Signal Not found');
        }
        $onu->setSignal($data['signal']);
        $onu->setSignal($data['temperature']);
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
                if (count($data) >= 5) {
                    $unRegOnuInfo['signal'] = "Rx/Tx {$data[1]}/{$data[3]}";
                    $unRegOnuInfo['temperature'] = "{$data[7]}";
                }
            }
        }

        return $unRegOnuInfo;
    }

}