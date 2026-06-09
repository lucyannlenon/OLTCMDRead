<?php

namespace LLENON\OltInformation\Adapters;

use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\Ethernet;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Exceptions\ClienteNotFund;
use LLENON\OltInformation\OltInterfaces\OnuDataInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\EthernetStatusCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\OnuStatusCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Command\OpticalInfoCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

class VSolOLTCmd implements OnuDataInterface
{
    private VSolEponConnectionInterface $connection;

    public function __construct(
        private readonly OLT $oltModel,
        private readonly Client $clientModel,
        ?VSolEponConnectionInterface $connection = null
    ) {
        (new OltCliProfileRegistry())->resolve($oltModel);
        $this->connection = $connection ?? new VSolEponConnection($oltModel);
    }

    public function getDadosDoCliente(): Client
    {
        try {
            $status = (new OnuStatusCommand($this->connection))->execute(
                (string) $this->clientModel->getMacAddress()
            );

            if ($status === null) {
                throw new ClienteNotFund("Invalid client {$this->clientModel->login}");
            }

            $pon = self::ponNumber($status->pon);
            $this->clientModel->slot = 0;
            $this->clientModel->pon = $pon;
            $this->clientModel->onuPosition = $status->onuId;
            $this->clientModel->status = $status->isOnline() ? 'ONLINE' : 'Offline';
            $this->clientModel->distance = "{$status->distance}m";
            $this->clientModel->uptime = $status->aliveTime;

            if (!$status->isOnline()) {
                return $this->clientModel;
            }

            $this->setOnlineDiagnostics($pon, $status->onuId);
            return $this->clientModel;
        } finally {
            $this->connection->disconnect();
        }
    }

    private function setOnlineDiagnostics(int $pon, int $onuId): void
    {
        $optical = (new OpticalInfoCommand($this->connection))->execute($pon, $onuId);
        if ($optical !== null) {
            $this->clientModel->signal = "{$optical->rxOpticalLevel}dBm";
            $this->clientModel->onuTemperatura = $optical->temperature;
        }

        $ethernet = (new EthernetStatusCommand($this->connection))->execute($pon, $onuId);
        if ($ethernet !== null) {
            $this->clientModel->ethernet = Ethernet::createFromArray([
                'speed' => $ethernet->speed,
                'status' => $ethernet->status,
                'speedConfig' => $ethernet->speedConfig,
                'loopStatus' => $ethernet->loopStatus,
            ]);
        }
    }

    private static function ponNumber(string $pon): int
    {
        if (!preg_match('~(?:^|/)(\d+)$~', $pon, $matches)) {
            throw new \UnexpectedValueException("Invalid VSOL EPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
