<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use InvalidArgumentException;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class AddOnuWIFICommand extends AbstractCommand
{
    private string $cmd = <<<COMMAND
conf t
interface gpon_olt-:PON:
onu :ONUID: type F670L sn :GEPONID:
exit
!
interface gpon_onu-:PON:::ONUID:
name :USERNAME:
sn-bind enable sn
tcont 5 profile 1G-UP
gemport 1 tcont 5
exit
interface vport-:PON:.:ONUID::1
service-port 1 user-vlan  :VLAN: vlan :VLAN:
!
pon-onu-mng gpon_onu-:PON:::ONUID:
service 1 gemport 1 vlan :VLAN:
## LINHA ABAIXO é OPCIONAL
##wan-ip 1 ipv4 mode pppoe username :USERNAME: password :PASSWORD: vlan-profile :VLAN: host 1
exit
exit
COMMAND;
    private Onu $onu;
    private int $id;


    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new EmptyReturnStringParser());
    }

    protected function getCommand(): string
    {
        $replaces = [
            'ONUID' => $this->getId(),
            'VLAN' => $this->onu->getVlan(),
            'PON' => $this->onu->getPon(),
            'GEPONID' => $this->onu->getGponId(),
            'USERNAME' => $this->onu->getUsername(),
            'PASSWORD' => $this->onu->getUsername(),
        ];
        $cmd = $this->cmd;
        foreach ($replaces as $k => $values) {
            $cmd = str_replace(":{$k}:", $values, $cmd);
        }

        return $cmd;
    }

    public function execute(Onu $onu): int
    {
        $this->onu = $onu;
        $lines = $this->exec();
        foreach ($lines as $line) {
            if (str_starts_with($line, "%Error")) {
                throw new InvalidArgumentException(implode("\n", $lines));
            }
        }

        return $this->id;
    }


    /**
     * @throws \Exception
     */
    private function getId(): int
    {

        $cmd = new NextIdCommand($this->connection);

        $this->id = $cmd->execute($this->onu->getPon());

        return $this->id;
    }
}