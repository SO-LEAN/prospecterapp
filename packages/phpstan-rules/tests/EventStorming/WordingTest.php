<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\Wording;

final class WordingTest extends TestCase
{
    public function testAnIntentionReadsAsASentenceOfItsWords(): void
    {
        self::assertSame('Add inactive period', Wording::ofIntention('AddInactivePeriod'));
    }

    public function testAnAcronymKeepsItsCapitals(): void
    {
        self::assertSame('Import CSV file', Wording::ofIntention('ImportCSVFile'));
    }

    public function testAPolicyReadsAsWhatItDoes(): void
    {
        self::assertSame('Identify booker', Wording::ofReaction('IdentifyBookerPolicy'));
    }

    public function testAnEventHandlerReadsAsWhatItDoes(): void
    {
        self::assertSame('Publish slot booked', Wording::ofReaction('PublishSlotBookedHandler'));
    }

    public function testAReadModelIsNamedAfterWhatItsQueryPresents(): void
    {
        self::assertSame('My booking page', Wording::ofReadModel('ConsultMyBookingPage', 'MyBookingPage'));
    }

    public function testAReadModelPresentingNothingIsNamedAfterWhatItsQueryAsksFor(): void
    {
        self::assertSame('Appointments', Wording::ofReadModel('ConsultAppointments', null));
    }

    public function testAMemberIsTheActorOfTheirRole(): void
    {
        self::assertSame('Member', Wording::ofActor('ROLE_USER'));
    }

    public function testTheConsoleIsThePlatformOperator(): void
    {
        self::assertSame('Platform operator', Wording::ofActor('operator'));
    }

    public function testTheDailyTasksAreTheDailySchedule(): void
    {
        self::assertSame('Daily schedule', Wording::ofActor('schedule'));
    }

    public function testAnotherRoleIsTheActorItNames(): void
    {
        self::assertSame('Account admin', Wording::ofActor('ROLE_ACCOUNT_ADMIN'));
    }
}
