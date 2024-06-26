<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\DTO\StatusLinkEnum;

class ListOnu
{

    public function __construct(
        private readonly ConnectionInterface $conn
    )
    {
    }

    public function execute($pon = "")
    {
        $pon = !empty($pon) ? $pon . " " : "";
        $cmd = "show interface gpon {$pon}onu";
        $text = $this->conn->exec($cmd);


        $lines = explode("\n", $text);

        return $this->prepareOnusForReturn($lines);

    }

    /**
     * @param array $matches
     * @return ONU
     */
    public function getOnu(array $matches): ONU
    {
        $onu = new ONU($matches[2]);
        $onu->setMAC($matches[3]);
        $onu->setPon($matches[1]);
        $onu->setStatus(StatusLinkEnum::getStatus($matches[4]));
        $onu->setName($matches[5]);
        return $onu;
    }

    /**
     * @param array $lines
     * @return array
     */
    public function prepareOnusForReturn(array $lines): array
    {
        $rows = [];
        // Regular expression pattern
        $pattern = '/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+\S+\s+(\S?+)/';
        // Iterate over each line
        foreach ($lines as $line) {
            // Match the pattern in the line
            preg_match($pattern, $line, $matches);
            // Check if a match is found
            if (count($matches) > 0) {
                // Extract and store the values in variables
                $itf = $matches[1];
                if (preg_match('/^\D/', $itf)) {
                    continue;
                }
                $onu = $this->getOnu($matches);

                $rows[] = $onu;
            }
        }
        return $rows;
    }
}