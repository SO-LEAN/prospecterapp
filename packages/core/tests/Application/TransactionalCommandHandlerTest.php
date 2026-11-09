<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalCommandHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryUnitOfWork;

class TransactionalCommandHandlerTest extends TestCase
{
    private InMemoryDatabase $database;
    private InMemoryUnitOfWork $unitOfWork;

    protected function setUp(): void
    {
        $this->database = new InMemoryDatabase();
        $this->unitOfWork = new InMemoryUnitOfWork($this->database);
    }

    public function testWhatTheHandlerWritesIsKept(): void
    {
        new TransactionalCommandHandler($this->handlerDoing($this->anAccountStored(...)), $this->unitOfWork)(new \stdClass());

        $this->assertFalse($this->database->isEmpty());
    }

    public function testWhatAFailingHandlerWroteIsThrownAway(): void
    {
        $failure = new \RuntimeException('The reaction failed');

        try {
            new TransactionalCommandHandler($this->handlerDoing(function () use ($failure): never {
                $this->anAccountStored();

                throw $failure;
            }), $this->unitOfWork)(new \stdClass());
            $this->fail('The failure was expected');
        } catch (\RuntimeException $thrown) {
            $this->assertSame($failure, $thrown);
            $this->assertTrue($this->database->isEmpty());
        }
    }

    public function testWithinAnOpenUnitTheHandlerJoinsIt(): void
    {
        $this->unitOfWork->begin();
        new TransactionalCommandHandler($this->handlerDoing($this->anAccountStored(...)), $this->unitOfWork)(new \stdClass());
        $this->unitOfWork->discard();

        $this->assertTrue($this->database->isEmpty(), 'only the outermost commit keeps what is written');
    }

    public function testARefusalDetectedAtTheCommitIsTranslatedAroundIt(): void
    {
        $taken = EmailAlreadyUsed::byAnother();

        try {
            new RefusalTranslatingCommandHandler(new TransactionalCommandHandler($this->handlerDoing(static fn (): null => null), $this->unitOfWorkRefusingAtTheCommit($taken)))(new \stdClass());
            $this->fail('The refusal was expected');
        } catch (UseCaseRefused $translated) {
            $this->assertSame($taken, $translated->getPrevious());
        }
    }

    private function anAccountStored(): void
    {
        $this->database->storeAccount(Account::open(new AccountId('0190e3c8-6c8e-7c5e-9d6a-3a1c1c4f2b10'), new LegalIdentity('SOLEAN IT'), new \DateTimeImmutable()));
    }

    /**
     * @param \Closure(): mixed $work
     */
    private function handlerDoing(\Closure $work): CommandHandler
    {
        return new readonly class($work) implements CommandHandler {
            /**
             * @param \Closure(): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            public function __invoke(\stdClass $command): void
            {
                ($this->work)();
            }
        };
    }

    private function unitOfWorkRefusingAtTheCommit(EmailAlreadyUsed $refusal): UnitOfWork
    {
        return new readonly class($refusal) implements UnitOfWork {
            public function __construct(private EmailAlreadyUsed $refusal)
            {
            }

            public function begin(): void
            {
            }

            public function commit(): void
            {
                throw $this->refusal;
            }

            public function discard(): void
            {
            }
        };
    }
}
