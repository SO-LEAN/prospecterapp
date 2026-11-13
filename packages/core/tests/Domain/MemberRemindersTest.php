<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Event\RemindersChanged;
use Prospecter\Core\Accounts\Domain\Exception\ReminderDelayNotOffered;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ReminderDelay;
use Prospecter\Core\Accounts\Domain\Model\Reminders;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member is reminded at one of the delays offered, and a change of their reminders records what happened.
 */
final class MemberRemindersTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string MEMBER = '0190a1c0-0000-7000-8000-0000000000aa';

    public function testAChangeOfTheRemindersRecordsIt(): void
    {
        $member = Member::register(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), new PersonId('0190a1c0-0000-7000-8000-0000000000bb'), Role::User, Role::Prospector);

        $member->changeReminders(new Reminders(false, ReminderDelay::HalfAnHour), at: new \DateTimeImmutable('2026-11-13 10:00'));

        $this->assertEquals([new RemindersChanged(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable('2026-11-13 10:00'))], $member->releaseEvents());
    }

    #[DataProvider('offeredDelays')]
    public function testADelayOffered(int $minutes): void
    {
        $this->assertSame($minutes, ReminderDelay::ofMinutes($minutes)->value);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function offeredDelays(): iterable
    {
        yield 'on time' => [0];
        yield 'an hour before' => [60];
    }

    #[DataProvider('delaysNotOffered')]
    public function testNoOtherDelay(int $minutes): void
    {
        $this->expectException(ReminderDelayNotOffered::class);

        ReminderDelay::ofMinutes($minutes);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function delaysNotOffered(): iterable
    {
        yield 'before the time' => [-5];
        yield 'between two delays' => [7];
        yield 'over an hour' => [120];
    }
}
