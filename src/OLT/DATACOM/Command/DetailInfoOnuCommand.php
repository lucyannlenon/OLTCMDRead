<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\DetailInfoStringParser;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class DetailInfoOnuCommand extends AbstractCommand
{

    protected ?string $pon;
    protected string $onuId;


    public function __construct(ConnectionInterface $connection)
    {
        $parser = new DetailInfoStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @param string $onuId
     * @return float | null
     * @throws \Exception
     */
    public function execute(string $pon, string $onuId): mixed
    {
        $this->pon = $pon;
        $this->onuId = $onuId;


        $data = $this->exec();
        return $this->getResult($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function getResult(array $data): mixed
    {
        return empty($data) ? null : $data;
    }

    protected function getCommand(): string
    {
        return "show interface gpon {$this->pon} onu {$this->onuId} ";
    }
}