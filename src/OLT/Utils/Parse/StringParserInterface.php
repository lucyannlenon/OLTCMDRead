<?php

namespace LLENON\OltInformation\OLT\Utils\Parse;

interface StringParserInterface
{
    public function parse(string $input): array;
}