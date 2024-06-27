<?php

namespace LLENON\OltInformation\Exceptions;

use JetBrains\PhpStorm\Pure;

class OltCommandException extends \Exception
{
    #[Pure] public function __construct(string                 $message,
                                        public readonly string $cause)
    {
        parent::__construct($message);
    }

}