<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

final readonly class FiberhomeTl1Config
{
    public function __construct(
        public string $gatewayAddress,
        public string $username,
        #[\SensitiveParameter]
        public string $password,
    ) {
        if ($gatewayAddress === '' || $username === '' || $password === '') {
            throw new \InvalidArgumentException('Fiberhome TL1 gateway configuration is incomplete.');
        }
    }
}
