<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates an event handler so that a reaction refuses the way every use case refuses. The domain's
 * BusinessException becomes a UseCaseRefused (ADR 0035). Anything else goes through. The command that published
 * the event refuses with it.
 */
final readonly class RefusalTranslatingEventHandler implements EventHandler
{
    public function __construct(private EventHandler $handler)
    {
    }

    public function __invoke(object $event): void
    {
        try {
            $this->handler->__invoke($event);
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
        }
    }
}
