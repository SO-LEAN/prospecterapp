<?php

namespace Tests\App\Shared\Messaging;

use App\Shared\Messaging\EmittingContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Event\AccountRelocated;
use Prospecter\Core\PersonalData\Domain\Event\PersonRenamed;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

class EmittingContextTest extends TestCase
{
    private const string ACCOUNT = '019a0000-0000-7000-8000-000000000001';
    private const string MEMBER = '019a0000-0000-7000-8000-000000000002';
    private const string SUBJECT = '019a0000-0000-7000-8000-000000000003';

    #[DataProvider('events')]
    public function testAnEventIsEmittedByTheContextOfItsNamespace(DomainEvent $event, string $context): void
    {
        $this->assertSame($context, EmittingContext::of($event));
    }

    /**
     * @return iterable<string, array{DomainEvent, string}>
     */
    public static function events(): iterable
    {
        $account = new AccountId(self::ACCOUNT);
        $member = new MemberId(self::MEMBER);
        $now = new \DateTimeImmutable('2026-11-09 10:00');

        yield 'Accounts' => [new AccountRelocated($account, $member, $now), 'Accounts'];
        yield 'PersonalData' => [new PersonRenamed($account, $member, new PersonId(self::SUBJECT), $now), 'PersonalData'];
        yield 'Prospecting' => [new ExchangeNoted($account, $member, new ExchangeId(self::SUBJECT), $now), 'Prospecting'];
    }
}
