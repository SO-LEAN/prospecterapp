<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * A reaction of another context to the opening of an account that throws, once the founder and their profile
 * are written by the real reactions subscribed before it.
 */
final readonly class FailingReaction implements EventHandler
{
    public function __construct(private \Throwable $failure)
    {
    }

    public function __invoke(AccountOpened $event): never
    {
        throw $this->failure;
    }
}
