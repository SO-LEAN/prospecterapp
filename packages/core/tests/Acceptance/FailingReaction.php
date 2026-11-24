<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * Another context's policy on an account's opening. Its command's handler fails (FailingReactionHandler).
 */
final readonly class FailingReaction implements Policy
{
    public function __invoke(AccountOpened $event): ReactToTheOpening
    {
        return new ReactToTheOpening($event->accountId, $event->founderId, $event->occurredAt);
    }
}
