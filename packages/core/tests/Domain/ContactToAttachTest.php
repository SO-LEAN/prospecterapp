<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachAttached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachHandedOver;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMet;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMetAgain;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A contact to attach is erased from the start of the 30th day after their last meeting, on that meeting's clock.
 * Every intention records its event (ADR 0146).
 */
final class ContactToAttachTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string ME = '0190a1c0-0000-7000-8000-000000000002';
    private const string ADA = '0190a1c0-0000-7000-8000-000000000003';
    private const string ACME = '0190a1c0-0000-7000-8000-000000000004';
    private const string ANOTHER = '0190a1c0-0000-7000-8000-000000000005';
    private const string NOW = '2026-11-18 10:00 Europe/Paris';

    public function testMeetingThemRecordsIt(): void
    {
        $this->assertEquals([new ContactToAttachMet(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ContactId(self::ADA), new \DateTimeImmutable(self::NOW))], $this->metOn('2026-11-20 14:00 Europe/Paris')->releaseEvents());
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function moments(): iterable
    {
        yield 'the evening before the 30th day' => ['2026-11-20 14:00 Europe/Paris', '2026-12-19 23:59 Europe/Paris', false];
        yield 'the start of the 30th day' => ['2026-11-20 14:00 Europe/Paris', '2026-12-20 00:00 Europe/Paris', true];
        yield 'a meeting late in the evening, the day read on its clock' => ['2026-11-20 23:30 Europe/Paris', '2026-12-20 00:00 Europe/Paris', true];
        yield 'a meeting on another clock' => ['2026-11-20 20:00 America/New_York', '2026-12-20 00:00 Europe/Paris', false];
        yield 'the start of the 30th day on that clock' => ['2026-11-20 20:00 America/New_York', '2026-12-20 00:00 America/New_York', true];
    }

    #[DataProvider('moments')]
    public function testItIsLeftUnattachedFromTheStartOfThe30thDayOnTheClockOfItsMeeting(string $meeting, string $now, bool $left): void
    {
        $this->assertSame($left, $this->metOn($meeting)->isLeftUnattachedAt(new \DateTimeImmutable($now)));
    }

    public function testALaterMeetingPushesTheErasureBack(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');
        $toAttach->releaseEvents();

        $toAttach->meetAgainAt(new \DateTimeImmutable('2026-12-10 08:00 UTC'), new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ContactToAttachMetAgain(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ContactId(self::ADA), new \DateTimeImmutable(self::NOW))], $toAttach->releaseEvents());
        $this->assertFalse($toAttach->isLeftUnattachedAt(new \DateTimeImmutable('2027-01-08 23:59 Europe/Paris')));
        $this->assertTrue($toAttach->isLeftUnattachedAt(new \DateTimeImmutable('2027-01-09 00:00 Europe/Paris')));
    }

    public function testAnEarlierMeetingChangesNothing(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');
        $toAttach->releaseEvents();

        $toAttach->meetAgainAt(new \DateTimeImmutable('2026-11-19 09:00 Europe/Paris'), new \DateTimeImmutable(self::NOW));

        $this->assertSame([], $toAttach->releaseEvents());
        $this->assertTrue($toAttach->isLeftUnattachedAt(new \DateTimeImmutable('2026-12-20 00:00 Europe/Paris')));
    }

    public function testAttachingRecordsTheOrganization(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');
        $toAttach->releaseEvents();

        $toAttach->attachTo(new OrganizationId(self::ACME), new MemberId(self::ME), new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ContactToAttachAttached(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ContactId(self::ADA), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $toAttach->releaseEvents());
    }

    public function testItIsErasedByItsHolderOnceLeftUnattached(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');
        $toAttach->releaseEvents();
        $at = new \DateTimeImmutable('2026-12-20 10:00 Europe/Paris');

        $toAttach->eraseLeftUnattached($at);

        $this->assertEquals([new ContactLeftUnattached(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ContactId(self::ADA), $at)], $toAttach->releaseEvents());
    }

    public function testItIsNotErasedBeforeItsDay(): void
    {
        $this->expectException(NotPossibleInItsState::class);

        $this->metOn('2026-11-20 14:00 Europe/Paris')->eraseLeftUnattached(new \DateTimeImmutable('2026-12-19 23:59 Europe/Paris'));
    }

    public function testOnlyItsHolderHoldsIt(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');

        $this->assertSame([true, false], [$toAttach->isHeldBy(new MemberId(self::ME)), $toAttach->isHeldBy(new MemberId(self::ACME))]);
    }

    public function testHandedOverToAnotherMemberTheyAreTheirs(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');
        $toAttach->releaseEvents();

        $toAttach->handOverTo(self::another(), new \DateTimeImmutable('2026-11-24 09:00 Europe/Paris'), new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ContactToAttachHandedOver(new AccountId(self::ACCOUNT), new MemberId(self::ANOTHER), new ContactId(self::ADA), new \DateTimeImmutable(self::NOW))], $toAttach->releaseEvents());
        $this->assertSame([true, false], [$toAttach->isHeldBy(new MemberId(self::ANOTHER)), $toAttach->isHeldBy(new MemberId(self::ME))]);
    }

    public function testHandedOverWithALaterMeetingTheErasureIsPushedBack(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');

        $toAttach->handOverTo(self::another(), new \DateTimeImmutable('2026-11-24 09:00 Europe/Paris'), new \DateTimeImmutable(self::NOW));

        $this->assertFalse($toAttach->isLeftUnattachedAt(new \DateTimeImmutable('2026-12-23 23:59 Europe/Paris')));
    }

    public function testHandedOverWithAnEarlierMeetingTheErasureStays(): void
    {
        $toAttach = $this->metOn('2026-11-20 14:00 Europe/Paris');

        $toAttach->handOverTo(self::another(), new \DateTimeImmutable('2026-11-19 09:00 Europe/Paris'), new \DateTimeImmutable(self::NOW));

        $this->assertFalse($toAttach->isLeftUnattachedAt(new \DateTimeImmutable('2026-12-19 23:59 Europe/Paris')));
    }

    private static function another(): Prospector
    {
        return new Prospector(new MemberId(self::ANOTHER), new AccountId(self::ACCOUNT), managesPersonalData: true);
    }

    private function metOn(string $meeting): ContactToAttach
    {
        return ContactToAttach::metBy(new Prospector(new MemberId(self::ME), new AccountId(self::ACCOUNT), managesPersonalData: true), new ContactId(self::ADA), new \DateTimeImmutable($meeting), new \DateTimeImmutable(self::NOW));
    }
}
