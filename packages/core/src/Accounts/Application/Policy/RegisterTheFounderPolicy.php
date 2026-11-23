<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Policy;

use Prospecter\Core\Accounts\Application\RegisterTheFounder\RegisterTheFounder;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an account opens, its founder becomes its first member.
 */
final readonly class RegisterTheFounderPolicy implements Policy
{
    public function __invoke(AccountOpened $event): RegisterTheFounder
    {
        return new RegisterTheFounder($event->accountId->value, $event->by->value, $event->founderProfile->value, $event->occurredAt);
    }
}
