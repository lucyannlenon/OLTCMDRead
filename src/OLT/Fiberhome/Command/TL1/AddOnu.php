<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;

class AddOnu extends AbstractTL1Command
{

    public function exec(mixed $params = null): array
    {
        $onu = $params['onu'];

        if (!$onu instanceof ONU) {
            throw new \Exception("params with key onu need instance of " . ONU::class);
        }

        $cmd = "ADD-ONU::OLTID={$this->conn->getIpTL1()}," .
            "PONID={$onu->getPon()}:CTAG::" .
            "AUTHTYPE=MAC," .
            "ONUTYPE={$onu->getOnuType()}," .
            "NAME={$onu->getName()}," .
            "ONUID={$onu->getId()};";
        $data = $this->conn->exec($cmd);
        if (preg_match('/\sENDESC=No error$/', $data)) {
            return ['success' => true];
        } elseif (preg_match('/ENDESC=([^ ]+)/', $data, $matches)) {
            $error = $matches[1];
            $message = "Error: $error\n";
        } else {
            $message = "Error not found in the string.\n";
        }
        return [
            'success' => false,
            'message' => $message
        ];
    }
}