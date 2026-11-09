<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingUnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryUnitOfWork;

class RefusalTranslatingUnitOfWorkTest extends TestCase
{
    public function testARefusalDetectedAtTheCommitIsTranslated(): void
    {
        $taken = EmailAlreadyUsed::byAnother();
        $unitOfWork = new RefusalTranslatingUnitOfWork($this->unitOfWorkFailingAtTheCommitWith($taken));
        $unitOfWork->begin();

        try {
            $unitOfWork->commit();
            $this->fail('The refusal was expected');
        } catch (UseCaseRefused $translated) {
            $this->assertSame(ErrorType::BusinessRuleViolated, $translated->type);
            $this->assertSame($taken, $translated->getPrevious());
        }
    }

    public function testAFailureOfTheInfrastructureGoesThroughUntouched(): void
    {
        $failure = new \RuntimeException('The database is gone');

        $this->expectExceptionObject($failure);

        new RefusalTranslatingUnitOfWork($this->unitOfWorkFailingAtTheCommitWith($failure))->commit();
    }

    public function testTheUnitItDecoratesIsBegunCommittedAndDiscarded(): void
    {
        $unitOfWork = new RefusalTranslatingUnitOfWork(new InMemoryUnitOfWork(new InMemoryDatabase()));
        $unitOfWork->begin();
        $unitOfWork->commit();
        $unitOfWork->discard();

        $this->expectException(\LogicException::class);

        $unitOfWork->commit();
    }

    private function unitOfWorkFailingAtTheCommitWith(\Throwable $failure): UnitOfWork
    {
        return new readonly class($failure) implements UnitOfWork {
            public function __construct(private \Throwable $failure)
            {
            }

            public function begin(): void
            {
            }

            public function commit(): void
            {
                throw $this->failure;
            }

            public function discard(): void
            {
            }
        };
    }
}
