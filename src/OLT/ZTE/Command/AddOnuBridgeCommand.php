<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\AddOnuBridgeStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class AddOnuBridgeCommand extends AbstractCommand
{
    private string $cmd = <<<COMMAND
conf t
interface gpon_olt-:PON:
onu :ONUID: type F601 sn :GEPONID:
exit
interface gpon_onu-:PON:::ONUID:
name 1
sn-bind enable sn
tcont 5 profile 1G-UP
gemport 1 tcont 5
exit
interface vport-:PON:.:ONUID::1
service-port 1 user-vlan  :VLAN: vlan :VLAN:
!
pon-onu-mng gpon_onu-:PON:::ONUID:
service 1 gemport 1 vlan :VLAN:
vlan port eth_0/1 mode tag vlan :VLAN:
exit
exit
COMMAND;
    private Onu $onu;


    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new AddOnuBridgeStringParser());
    }

    public function getCommand(): string
    {
        $replaces = [
            'ONUID' => $this->getId(),
            'VLAN' => $this->onu->getVlan(),
            'PON' => $this->onu->getPon(),
            'GEPONID' => $this->onu->getGponId(),
        ];
        $cmd = $this->cmd;
        foreach ($replaces as $k => $values) {
            $cmd = str_replace(":{$k}:", $values, $cmd);
        }

        return $cmd;
    }

    public function execute(Onu $onu): void
    {
        $this->onu = $onu;
        $lines = $this->exec();
        foreach ($lines as $line) {
            if (str_starts_with($line, "%Error")) {
                throw new \InvalidArgumentException(implode("\n", $lines));
            }
        }
    }


    private function getId(): int
    {
        $cmd = new NextIdCommand($this->connection);

        return $cmd->execute($this->onu->getPon());
    }
}