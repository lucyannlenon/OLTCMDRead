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

    public static function fromEnvironment(): self
    {
        $gatewayAddress = getenv('IPSERVER_TL1');
        $username = getenv('USERNAME_TL1');
        $password = getenv('PASSWORD_TL1');

        if ($gatewayAddress === false || $username === false || $password === false) {
            throw new \InvalidArgumentException(
                'Fiberhome TL1 requires IPSERVER_TL1, USERNAME_TL1, and PASSWORD_TL1.'
            );
        }

        return new self($gatewayAddress, $username, $password);
    }
}
