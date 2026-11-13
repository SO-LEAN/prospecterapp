<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMyReminders;

final readonly class ConsultMyReminders
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
