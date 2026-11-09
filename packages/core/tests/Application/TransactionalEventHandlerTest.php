<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalEventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryUnitOfWork;

class TransactionalEventHandlerTest extends TestCase
{
    private InMemoryDatabase $database;
    private InMemoryUnitOfWork $unitOfWork;

    protected function setUp(): void
    {
        $this->database = new InMemoryDatabase();
        $this->unitOfWork = new InMemoryUnitOfWork($this->database);
    }

    public function testWhatTheReactionWritesIsKept(): void
    {
        new TransactionalEventHandler($this->reactionDoing($this->anAccountStored(...)), $this->unitOfWork)(new \stdClass());

        $this->assertFalse($this->database->isEmpty());
    }

    public function testAFailingReactionThrowsAwayWhatTheCommandWroteBeforeItOnceTheCommandEnds(): void
    {
        $this->unitOfWork->begin();
        $this->anAccountStored();

        try {
            new TransactionalEventHandler($this->reactionDoing(static fn () => throw new \RuntimeException('The reaction failed')), $this->unitOfWork)(new \stdClass());
            $this->fail('The failure was expected');
        } catch (\RuntimeException) {
            $this->unitOfWork->discard();
            $this->assertTrue($this->database->isEmpty());
        }
    }

    public function testWhatIsWrittenAfterAFailedReactionIsThrownAwayToo(): void
    {
        $this->unitOfWork->begin();

        try {
            new TransactionalEventHandler($this->reactionDoing(static fn () => throw new \RuntimeException('The reaction failed')), $this->unitOfWork)(new \stdClass());
        } catch (\RuntimeException) {
            new TransactionalEventHandler($this->reactionDoing($this->anAccountStored(...)), $this->unitOfWork)(new \stdClass());
        }

        $this->unitOfWork->discard();

        $this->assertTrue($this->database->isEmpty(), 'the event bus still calls the other reactions');
    }

    private function anAccountStored(): void
    {
        $this->database->storeAccount(Account::open(new AccountId('0190e3c8-6c8e-7c5e-9d6a-3a1c1c4f2b10'), new LegalIdentity('SOLEAN IT'), new \DateTimeImmutable()));
    }

    /**
     * @param \Closure(): mixed $work
     */
    private function reactionDoing(\Closure $work): EventHandler
    {
        return new readonly class($work) implements EventHandler {
            /**
             * @param \Closure(): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            public function __invoke(\stdClass $event): void
            {
                ($this->work)();
            }
        };
    }
}
