<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates a query handler so that it refuses the way every use case refuses. The domain's BusinessException
 * becomes a UseCaseRefused (ADR 0035). Anything else goes through. So the handlers stay free of error handling. A
 * client asks its queries through this decorator. It gives each query the output port that receives its answer
 * (ADR 0085).
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
