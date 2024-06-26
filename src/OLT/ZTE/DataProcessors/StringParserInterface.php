<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

interface StringParserInterface
{
    public function parse(string $input): array;
}