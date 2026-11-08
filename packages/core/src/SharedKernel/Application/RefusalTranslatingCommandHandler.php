<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates a command handler so that it refuses the way every use case refuses: the BusinessException of the
 * domain becomes a UseCaseRefused (ADR 0035), anything else goes through. The handlers stay free of error
 * handling; a client sends its commands through this decorator.
 */
final readonly class RefusalTranslatingCommandHandler implements CommandHandler
{
    public function __construct(private CommandHandler $handler)
    {
    }

    public function __invoke(object $command): void
    {
        try {
            $this->handler->__invoke($command);
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
        }
    }
}
