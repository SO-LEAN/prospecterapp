<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Policy;

use Prospecter\Core\Accounts\Application\Member\RegisterTheFounder\RegisterTheFounder;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an account opens, its founder becomes its first member.
 */
final readonly class RegisterTheFounderPolicy implements Policy
{
    public function __invoke(AccountOpened $event): RegisterTheFounder
    {
        return new RegisterTheFounder(
            accountId: $event->accountId->value,
            founderId: $event->by->value,
            founderProfileId: $event->founderProfile->value,
            executedAt: $event->occurredAt,
        );
    }
}
