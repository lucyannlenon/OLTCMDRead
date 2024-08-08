<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\ListOnuParse;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class RemoveOnuCommand extends AbstractCommand
{

    private ?string $pon;
    private string $onuId;

    private string $servicePortId;

    public function __construct(ConnectionInterface $connection)
    {
        $parser = new EmptyReturnStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @param string $onuId
     * @return void
     * @throws \Exception
     */
    public function execute(string $pon, string $onuId): void
    {
        $this->pon = $pon;
        $this->onuId = $onuId;
        $command = new GetServicePortCommand($this->connection);
        $id = $command->execute($pon, $onuId);
        if (!$id)
            throw new \InvalidArgumentException("Invalid onu id: {$onuId}, pon {$pon} not found in olt");

        $this->servicePortId = $id;

        $this->exec();

    }

    protected function getCommand(): string
    {
        $command = <<<'EOF'
config terminal
interface gpon %s
no onu %s
exit
no service-port %s
commit
EOF;
        $cmd = sprintf($command, $this->pon, $this->onuId, $this->servicePortId);
        return $cmd;
    }
}