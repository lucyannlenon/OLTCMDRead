<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class AddOnuBridgeCommand extends AbstractTL1Command
{
    private \LLENON\OltInformation\OLT\Dto\Onu $onu;
    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new EmptyReturnStringParser();
        parent::__construct($connection, $parser);
    }



    public function execute(\LLENON\OltInformation\OLT\Dto\Onu $onu): array
    {
        $this->onu = $onu;

        if (!$onu) {
            throw new \Exception("params with key onu need instance of " . Onu::class);
        }
        $data = $this->exec();



        return [
            'success' => false
        ];
    }

    protected function getCommand(): string
    {
        return "ADD-ONU::OLTID={$this->getIpOlt()}," .
            "PONID={$this->onu->getPon()}:CTAG::" .
            "AUTHTYPE=MAC," .
            "ONUTYPE={$this->onu->getModel()}," .
            "NAME={$this->onu->getUsername()}," .
            "ONUID={$this->onu->getGponId()};";
    }
}