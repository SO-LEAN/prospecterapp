<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * A reaction of another context to the opening of an account that throws, after writing what it is given to write.
 */
final readonly class FailingReaction implements EventHandler
{
    /**
     * @param (\Closure(AccountOpened): void)|null $writing what it writes before it throws
     */
    public function __construct(private \Throwable $failure, private ?\Closure $writing = null)
    {
    }

    public function __invoke(AccountOpened $event): never
    {
        $this->writing?->__invoke($event);

        throw $this->failure;
    }
}
