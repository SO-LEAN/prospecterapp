<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates a query handler so that it refuses the way every use case refuses: the BusinessException of the
 * domain becomes a UseCaseRefused (ADR 0035), anything else goes through. The handlers stay free of error
 * handling; a client asks its queries through this decorator, giving each the output port it presents its answer
 * to (ADR 0085).
 */
final readonly class RefusalTranslatingQueryHandler implements QueryHandler
{
    public function __construct(private QueryHandler $handler)
    {
    }

    public function __invoke(object $query, object $output): void
    {
        try {
            $this->handler->__invoke($query, $output);
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
        }
    }
}
