<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;

/**
 * The work of another context's reaction to an account's opening. It writes what it is given to write, then throws.
 */
final readonly class FailingReactionHandler implements CommandHandler
{
    /**
     * @param (\Closure(ReactToTheOpening): void)|null $writing what it writes before it throws
     */
    public function __construct(private \Throwable $failure, private ?\Closure $writing = null)
    {
    }

    public function __invoke(ReactToTheOpening $command): never
    {
        $this->writing?->__invoke($command);

        throw $this->failure;
    }
}
