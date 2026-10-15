<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates a handler so that it refuses the way every use case refuses: the BusinessException of the
 * domain becomes a UseCaseRefused (ADR 0035), anything else goes through. The handlers stay free of
 * error handling; a client calls them through this decorator.
 */
final readonly class RefusalTranslatingHandler
{
    /** @var \Closure(object): mixed */
    private \Closure $handler;

    public function __construct(CommandHandler|QueryHandler $handler)
    {
        $this->handler = $handler->__invoke(...);
    }

    public function __invoke(object $request): mixed
    {
        try {
            return ($this->handler)($request);
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
        }
    }
}
