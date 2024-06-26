<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;

class DiscoveryOnu extends AbstractTL1Command
{


    public function exec(mixed $params = null): array
    {

        $cmd = "LST-UNREGONU::OLTID={$this->conn->getIpTL1()}:CTAG::;";

        $data = $this->conn->exec($cmd);
        $ret = $this->extractInformation($data);
        return $ret;

    }

    private function extractInformation($string): array
    {
        $lines = explode("\n", $string);
        $unRegOnuInfo = [];

        foreach ($lines as $line) {
            if (str_contains($line, '-----') || str_contains($line, 'SLOTNO')) {
                continue;
            }
            if (!empty(trim($line))) {
                $data = preg_split('/\t/', $line);
                if (count($data) >= 5) {
                    $onu = $this->createOnu($data);
                    if ($onu) {
                        $unRegOnuInfo[] = $onu;
                    }
                }
            }
        }

        return $unRegOnuInfo;
    }

    /**
     * @param array|false $data
     * @return ONU|null
     */
    public function createOnu(array|false $data): ONU|null
    {
        if (!$data)
            return null;

        $onu = new ONU($data[2]);
        $onu->setPon("{$data[0]}-{$data[1]}");
        var_dump($data);
        $onu->setOnuType($data[7]);
        return $onu;
    }

}