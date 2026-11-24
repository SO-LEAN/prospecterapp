<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Account\ConsultMyAccount;

final readonly class ConsultMyAccount
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
