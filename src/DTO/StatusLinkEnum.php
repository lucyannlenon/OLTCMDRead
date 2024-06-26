<?php

namespace LLENON\OltInformation\DTO;

enum StatusLinkEnum: string
{
    case ON = "ON";
    case OFF = "OFF";
    case POWER_OF = "Power-Off";
    case LOS = "LOS";

    public static function getStatus(string $status): self
    {
        $status = strtoupper($status);
        return match ($status) {
            "ON", "UP" => StatusLinkEnum::ON,
            "OFF", "DOWN" => StatusLinkEnum::OFF,
            "LOS", => StatusLinkEnum::LOS,
            "Power-Off", => StatusLinkEnum::POWER_OF,
        };
    }
}
