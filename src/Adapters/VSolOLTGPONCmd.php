<?php

namespace LLENON\OltInformation\Adapters;

use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\Ethernet;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Exceptions\ClienteNotFund;
use LLENON\OltInformation\OltInterfaces\OnuDataInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\DistanceCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\EthernetStatusCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\FindOnuCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\OnuStatusCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\OpticalInfoCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Command\UptimeCommand;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnection;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

/**
 * @deprecated Legacy adapter. Use the versioned layer (VSolGponFeatureAdapter /
 *             OLT\VSOL\GPON\Command\*) instead.
 */
class VSolOLTGPONCmd implements OnuDataInterface
{
    private VSolGponConnectionInterface $connection;

    public function __construct(
        private readonly OLT $oltModel,
        private readonly Client $clientModel,
        ?VSolGponConnectionInterface $connection = null
    ) {
        (new OltCliProfileRegistry())->resolve($oltModel);
        $this->connection = $connection ?? new VSolGponConnection($oltModel);
    }

    public function getDadosDoCliente(): Client
    {
        try {
            $onu = (new FindOnuCommand($this->connection))->execute(
                (string) $this->clientModel->getGponName()
            );

            if ($onu === null) {
                throw new ClienteNotFund("Invalid client {$this->clientModel->login}");
            }

            $pon = self::ponNumber($onu->getPon());
            $onuId = (int) $onu->getId();
            $status = (new OnuStatusCommand($this->connection))->execute($pon, $onuId);

            $this->clientModel->slot = 0;
            $this->clientModel->pon = $pon;
            $this->clientModel->onuPosition = $onuId;
            $this->clientModel->status = $status?->isOnline() ? 'ONLINE' : 'Offline';

            if ($status === null || !$status->isOnline()) {
                return $this->clientModel;
            }

            $this->setOnlineDiagnostics($pon, $onuId);
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

        $distance = (new DistanceCommand($this->connection))->execute($pon, $onuId);
        $this->clientModel->distance = $distance === null ? 'Not found.' : "{$distance}m";

        $this->clientModel->uptime =
            (new UptimeCommand($this->connection))->execute($pon, $onuId)
            ?? 'Alive time not found.';

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
            throw new \UnexpectedValueException("Invalid VSOL GPON PON '{$pon}'.");
        }

        return (int) $matches[1];
    }
}
