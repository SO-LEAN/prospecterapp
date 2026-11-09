<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Decorates the unit of work a client opens around several commands, one gesture: a refusal detected when it
 * commits refuses the way every use case refuses, a UseCaseRefused (ADR 0035), anything else goes through.
 */
final readonly class RefusalTranslatingUnitOfWork implements UnitOfWork
{
    public function __construct(private UnitOfWork $unitOfWork)
    {
    }

    public function begin(): void
    {
        $this->unitOfWork->begin();
    }

    /**
     * @throws UseCaseRefused a refusal detected when writing
     */
    public function commit(): void
    {
        try {
            $this->unitOfWork->commit();
        } catch (BusinessException $refusal) {
            throw UseCaseRefused::because($refusal);
        }
    }

    public function discard(): void
    {
        $this->unitOfWork->discard();
    }
}
