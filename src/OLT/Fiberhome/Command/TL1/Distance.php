<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;

class Distance extends AbstractTL1Command
{


    public function exec(mixed $params = null): array
    {
        $onu = $params['onu'];

        if (!$onu instanceof ONU) {
            throw new \Exception("params with key onu need instance of " . ONU::class);
        }
        //$this->addVlan($onu, $params['vlan']);
        $cmd = <<<EOF
SET-WANSERVICE::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::STATUS=1,MODE=2,CONNTYPE=2,VLAN={$params['vlan']},COS=7,QOS=1,NAT=1,IPMODE=3,PPPOEPROXY=2,PPPOEUSER={$params['username']},PPPOEPASSWD={$params['password']},PPPOENAME=pppoe,PPPOEMODE=1,UPORT=1;
EOF;
        $cmd ="SET-WANSERVICE::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::STATUS=1,MODE=2,CONNTYPE=2,VLAN={$params['vlan']},COS=7,QOS=1,NAT=1,IPMODE=3,IPSTACKMODE=1,IP6SRCTYPE=0,IP6PREFIXSRCTYPE=0,PPPOEPROXY=2,PPPOEUSER={$params['username']},PPPOEPASSWD={$params['password']},PPPOENAME=pppoe,PPPOEMODE=1,SSID=5;";

        $retCommand = $this->conn->exec($cmd);
        var_dump($retCommand);
        $cmd = "SET-WANSERVICE::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::STATUS=1,MODE=2,CONNTYPE=2,VLAN=2000,COS=1,QOS=2,NAT=1,IPMODE=3,PPPOEPROXY=2,PPPOEUSER=usuario,PPPOEPASSWD=senha,PPPOENAME=,PPPOEMODE=1,UPORT=1;";
        $cmd ="SET-WANSERVICE::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::STATUS=1,MODE=2,CONNTYPE=2,VLAN={$params['vlan']},COS=7,QOS=1,NAT=1,IPMODE=3,PPPOEPROXY=2,PPPOEUSER={$params['username']},PPPOEPASSWD={$params['password']},PPPOENAME=pppoe,PPPOEMODE=1,WANSVC=1;";
        $retCommand = $this->conn->exec($cmd);
        var_dump($retCommand);

        $cmd = "LST-ONUSERVICESTATUS::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()}:CTAG::;";
        $retCommand = $this->conn->exec($cmd);
        var_dump($retCommand);
//        $data = $this->extractInformation($retCommand);
//        if (empty($data)) {
//            throw new \Exception('Signal Not found');
//        }
        return [];

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

    private function addVlan(ONU $onu, mixed $vlan):void
    {
        $cmd ="CFG-LANPORTVLAN::OLTID={$this->conn->getIpTL1()},PONID={$onu->getPon()},ONUIDTYPE=MAC,ONUID={$onu->getId()},ONUPORT=1-1-1-1:CTAG::CVLAN={$vlan};" ;
        $data = $this->conn->exec($cmd) ;
        var_dump($data);
    }

}