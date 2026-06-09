<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\BasicOnuInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\OnuStatusStringParser;

final class ListOnuCommand
{
    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly BasicOnuInfoStringParser $basicParser = new BasicOnuInfoStringParser(),
        private readonly OnuStatusStringParser $statusParser = new OnuStatusStringParser()
    ) {
    }

    /** @return array<Onu> */
    public function execute(?int $pon = null): array
    {
        if ($pon !== null && ($pon < 1 || $pon > 4)) {
            throw new \InvalidArgumentException('VSOL EPON PON must be between 1 and 4.');
        }

        $suffix = $pon === null ? 'all' : "pon {$pon},all";
        $basicResponse = $this->connection->exec("show onu basic-info {$suffix}");
        $statusResponse = $this->connection->exec(
            $pon === null ? 'show onu status all' : "show onu status pon {$pon},all"
        );

        $basicOnus = $basicResponse === false ? [] : $this->basicParser->parse($basicResponse);
        $statuses = $statusResponse === false ? [] : $this->statusParser->parse($statusResponse);
        $basicByAddress = [];

        foreach ($basicOnus as $onu) {
            $basicByAddress[$onu->getPon() . ':' . $onu->getId()] = $onu;
        }

        $onus = [];
        foreach ($statuses as $status) {
            $basic = $basicByAddress[$status->pon . ':' . $status->onuId] ?? null;
            $onus[] = (new Onu())
                ->setPon($status->pon)
                ->setId((string) $status->onuId)
                ->setModel($basic?->getModel() ?? '')
                ->setGponId($status->macAddress)
                ->setState($status->status)
                ->setOfflineTimes($status->aliveTime);
        }

        return $onus;
    }
}
