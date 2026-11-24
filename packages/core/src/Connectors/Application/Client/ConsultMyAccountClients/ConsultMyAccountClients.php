<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\ConsultMyAccountClients;

final readonly class ConsultMyAccountClients
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
