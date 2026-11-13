<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCancelled;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMadeConform;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedDone;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedMissed;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeVenueCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\InvalidVideoCallLink;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToMakeConform;
use Prospecter\Core\Prospecting\Domain\Exception\NotTheHolder;
use Prospecter\Core\Prospecting\Domain\Exception\NotUpcoming;
use Prospecter\Core\Prospecting\Domain\Exception\NotYetDue;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;
use Prospecter\Core\Prospecting\Domain\Exception\PlannedExchangeInThePast;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Double\StateOf;

class ExchangeTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string COLLEAGUE = '0190a1c0-0000-7000-8000-000000000002';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000003';
    private const string CONTACT = '0190a1c0-0000-7000-8000-000000000004';
    private const string ACME = '0190a1c0-0000-7000-8000-000000000005';
    private const string GROUP = '0190a1c0-0000-7000-8000-000000000006';
    private const string ATTACHMENT = '0190a1c0-0000-7000-8000-000000000007';
    private const string FILE = '6f1c2a9e0b7d4c3a8e5f1b2c3d4e5f60';
    private const string NOW = '2026-10-18 19:00';
    private const string MEET = 'https://meet.google.com/abc-defg-hij';

    public function testNotingRecordsIt(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Sms, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->assertEquals([new ExchangeNoted(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testPlanningRecordsIt(): void
    {
        $exchange = $this->planned();

        $this->assertEquals([new ExchangePlanned(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)['status']);
    }

    public function testANotedExchangeIsDone(): void
    {
        $this->assertSame(ExchangeStatus::Done, StateOf::the($this->anExchange())['status']);
    }

    public function testAnExchangeAMomentAheadIsPlanned(): void
    {
        $this->assertSame(ExchangeStatus::Planned, StateOf::the($this->planned('+1 second'))['status']);
    }

    public function testAnExchangeForNowIsNotPlanned(): void
    {
        $this->expectException(PlannedExchangeInThePast::class);

        $this->planned('+0 second');
    }

    public function testCorrectingTheTextOfAPlannedExchangeKeepsItsDuration(): void
    {
        $exchange = $this->planned();

        $exchange->correctText('Ordre du jour', by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(45, StateOf::the($exchange)['facts']->duration?->minutes);
    }

    public function testAPlannedExchangeIsNotCorrected(): void
    {
        $this->expectException(NotPossibleInItsState::class);

        $this->planned()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAnExchangeHappeningNowIsNoted(): void
    {
        $this->expectNotToPerformAssertions();

        $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW)));
    }

    public function testAnExchangeAMomentAheadIsInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->noted(new ExchangeFacts(Channel::Email, Direction::Inbound, new \DateTimeImmutable(self::NOW)->modify('+1 second')));
    }

    public function testAMessageMissedIsRefusedNamingItsChannel(): void
    {
        try {
            $this->noted(new ExchangeFacts(Channel::Sms, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Missed);
            $context = null;
        } catch (ChannelNotMissed $refusal) {
            $context = $refusal->context;
        }

        $this->assertSame(['channel' => 'sms'], $context);
    }

    #[DataProvider('channels')]
    public function testOnlyACallAMeetingOrAVideoCallIsMissed(Channel $channel): void
    {
        try {
            $status = StateOf::the($this->noted(new ExchangeFacts($channel, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Missed))['status'];
        } catch (ChannelNotMissed) {
            $status = null;
        }

        $this->assertSame($channel->mayBePlanned() ? ExchangeStatus::Missed : null, $status);
    }

    /**
     * @return iterable<string, array{Channel}>
     */
    public static function channels(): iterable
    {
        foreach (Channel::cases() as $channel) {
            yield $channel->name => [$channel];
        }
    }

    public function testACorrectionSaysWhetherItWasMissed(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Missed);

        $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Done, StateOf::the($exchange)['status']);
    }

    public function testAnExchangeCorrectedIntoAMessageIsNotMissed(): void
    {
        $this->expectException(ChannelNotMissed::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Sms, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Missed, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testMarkedDoneItTookPlaceAtTheMomentSaidWithItsReport(): void
    {
        $exchange = $this->due();
        $exchange->releaseEvents();
        $tookPlaceAt = new \DateTimeImmutable(self::NOW)->modify('-50 minutes');

        $exchange->markDone($tookPlaceAt, 'Mission en mars', by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([ExchangeStatus::Done, $tookPlaceAt, 'Mission en mars', 45], [StateOf::the($exchange)['status'], StateOf::the($exchange)['facts']->occurredAt, StateOf::the($exchange)['facts']->text, StateOf::the($exchange)['facts']->duration?->minutes]);
        $this->assertEquals([new ExchangeMarkedDone(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testMarkedDoneItIsNeverInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->due()->markDone(new \DateTimeImmutable(self::NOW)->modify('+1 second'), null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testMarkingMissedRecordsIt(): void
    {
        $exchange = $this->due();
        $exchange->releaseEvents();

        $exchange->markMissed(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Missed, StateOf::the($exchange)['status']);
        $this->assertEquals([new ExchangeMarkedMissed(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testAnExchangeIsNotMarkedBeforeItsTime(): void
    {
        $this->expectException(NotYetDue::class);

        $this->planned()->markMissed(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAnExchangeIsMarkedFromTheMomentItWasPlannedFor(): void
    {
        $exchange = $this->planned();
        $itsTime = StateOf::the($exchange)['facts']->occurredAt;

        $exchange->markMissed(by: $this->me()->id, at: $itsTime);

        $this->assertSame(ExchangeStatus::Missed, StateOf::the($exchange)['status']);
    }

    public function testForgettingItsContactAtTheMomentOfAPlannedExchangeLeavesItToItsHolder(): void
    {
        $exchange = $this->planned();

        $exchange->forgetItsContact(by: $this->me()->id, at: StateOf::the($exchange)['facts']->occurredAt);

        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)['status']);
    }

    public function testMovedItKeepsItsDuration(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+2 days');

        $exchange->moveTo($later, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([$later, 45], [StateOf::the($exchange)['facts']->occurredAt, StateOf::the($exchange)['facts']->duration?->minutes]);
        $this->assertEquals([new ExchangeMoved(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testAnExchangeIsNotMovedIntoThePast(): void
    {
        $this->expectException(PlannedExchangeInThePast::class);

        $this->planned()->moveTo(new \DateTimeImmutable(self::NOW), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testCancellingRecordsWhoCalledItOff(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();

        $exchange->cancel(Canceller::Contact, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Cancelled, StateOf::the($exchange)['status']);
        $this->assertEquals(new Cancellation($this->me()->id, new \DateTimeImmutable(self::NOW), Canceller::Contact), StateOf::the($exchange)['cancellation']);
        $this->assertEquals([new ExchangeCancelled(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testAColleagueDoesNotHandleAPlannedExchange(): void
    {
        $this->expectException(NotTheHolder::class);

        $this->planned()->cancel(null, by: $this->colleagueWithoutTheRight()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAnExchangeNoLongerToComeIsNotHandled(): void
    {
        $this->expectException(NotUpcoming::class);

        $this->anExchange()->cancel(null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testForgettingItsContactCancelsAnExchangeToCome(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();

        $exchange->forgetItsContact(by: $this->colleagueWithoutTheRight()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals(new Cancellation($this->colleagueWithoutTheRight()->id, new \DateTimeImmutable(self::NOW), null), StateOf::the($exchange)['cancellation']);
        $this->assertEquals([
            new ExchangeContactForgotten(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW)),
            new ExchangeCancelled(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW)),
        ], $exchange->releaseEvents());
    }

    public function testForgettingItsContactLeavesAPlannedExchangeWhoseTimeHasComeToItsHolder(): void
    {
        $exchange = $this->due();

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)['status']);
    }

    #[DataProvider('channelsButTheMeeting')]
    public function testOnlyAMeetingHasAPlace(Channel $channel): void
    {
        $this->expectException(PlaceOnlyForAMeeting::class);

        new ExchangeFacts($channel, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: 'Café de Flore');
    }

    /**
     * @return iterable<string, array{Channel}>
     */
    public static function channelsButTheMeeting(): iterable
    {
        foreach (Channel::cases() as $channel) {
            if (Channel::Meeting !== $channel) {
                yield $channel->name => [$channel];
            }
        }
    }

    #[DataProvider('channelsButTheVideoCall')]
    public function testOnlyAVideoCallHasALink(Channel $channel): void
    {
        $this->expectException(LinkOnlyForAVideoCall::class);

        new ExchangeFacts($channel, Direction::Outbound, new \DateTimeImmutable(self::NOW), link: new VideoCallLink(self::MEET));
    }

    /**
     * @return iterable<string, array{Channel}>
     */
    public static function channelsButTheVideoCall(): iterable
    {
        foreach (Channel::cases() as $channel) {
            if (Channel::VideoCall !== $channel) {
                yield $channel->name => [$channel];
            }
        }
    }

    public function testABlankPlaceIsNoPlace(): void
    {
        $this->assertNull(new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: " \n ")->place);
    }

    public function testAMeetingKeepsItsPlaceAndAVideoCallItsLink(): void
    {
        $this->assertSame('Café de Flore', ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable(self::NOW), Duration::inMinutes(null), null, 'Café de Flore')->place);
        $this->assertEquals(new VideoCallLink(self::MEET), ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable(self::NOW), Duration::inMinutes(null), null, link: new VideoCallLink(self::MEET))->link);
    }

    #[DataProvider('notWebAddresses')]
    public function testAVideoCallLinkIsAWebAddress(string $link): void
    {
        $this->expectException(InvalidVideoCallLink::class);

        VideoCallLink::orNone($link);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notWebAddresses(): iterable
    {
        yield 'words' => ['réunion de mardi'];
        yield 'without its scheme' => ['meet.google.com/abc-defg-hij'];
        yield 'another scheme' => ['ftp://meet.example.com/abc'];
        yield 'a script' => ['javascript:alert(1)'];
    }

    public function testAVideoCallLinkLeftBlankIsNone(): void
    {
        $this->assertNull(VideoCallLink::orNone('  '));
        $this->assertNull(VideoCallLink::orNone(null));
    }

    public function testAVideoCallLinkIsKeptWithoutTheSpacesAround(): void
    {
        $this->assertSame(self::MEET, VideoCallLink::orNone(sprintf(' %s ', self::MEET))?->url);
    }

    public function testCorrectingTheVenueKeepsTheOtherFactsAndRecordsWhoCorrected(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 hour');

        $exchange->correctVenue('Les Deux Magots', null, by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable(self::NOW)->modify('+1 day'), Duration::inMinutes(45), null, 'Les Deux Magots'), StateOf::the($exchange)['facts']);
        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)['lastCorrection']);
        $this->assertEquals([new ExchangeVenueCorrected(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testACorrectionOfTheWholeKeepsThePlaceWhileItStaysAMeeting(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: 'Café de Flore'));

        $exchange->correct(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), 'Déjeuner'), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame('Café de Flore', StateOf::the($exchange)['facts']->place);
    }

    public function testACorrectionOfTheWholeDropsThePlaceOfAMeetingNoLongerOne(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: 'Café de Flore'));

        $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertNull(StateOf::the($exchange)['facts']->place);
    }

    public function testACorrectionOfTheWholeKeepsTheLinkWhileItStaysAVideoCall(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::VideoCall, Direction::Outbound, new \DateTimeImmutable(self::NOW), link: new VideoCallLink(self::MEET)));

        $exchange->correct(new ExchangeFacts(Channel::VideoCall, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals(new VideoCallLink(self::MEET), StateOf::the($exchange)['facts']->link);
    }

    public function testABlankTextIsNoText(): void
    {
        $this->assertNull(new ExchangeFacts(Channel::Other, Direction::Inbound, new \DateTimeImmutable(self::NOW), " \n ")->text);
    }

    public function testNoLongerKeptForAnOrganizationIsRecorded(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));
        $exchange->releaseEvents();

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new OrganizationId(self::GROUP)], StateOf::the($exchange)['keptFor']);
        $this->assertTrue($exchange->isKeptForAnOrganization());
        $this->assertEquals([new ExchangeNoLongerKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testKeptForNoOrganizationOnceTheLastIsGone(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
        $exchange->releaseEvents();
        $exchange->stopKeepingFor(new OrganizationId(self::GROUP), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertFalse($exchange->isKeptForAnOrganization());
        $this->assertEquals([
            new ExchangeNoLongerKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::GROUP), new \DateTimeImmutable(self::NOW)),
            new ExchangeLeftWithoutOrganization(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW)),
        ], $exchange->releaseEvents());
    }

    public function testItsContactForgottenEverythingItsChannelAllowsButHandlingAPlannedExchangeIsOffered(): void
    {
        $offered = [ExchangeIntent::CorrectExchange, ExchangeIntent::CorrectText, ExchangeIntent::MakeConform, ExchangeIntent::EraseExchange, ExchangeIntent::AttachFile, ExchangeIntent::RemoveAttachment];

        $this->assertSame($offered, $this->possibleIntentsOf($this->withItsContactForgotten(), $this->me()));
    }

    public function testAFileIsReadWithTheRightToManagePersonalData(): void
    {
        $this->assertSame('devis.pdf', $this->withAnAttachment()->attachmentReadBy($this->me(), new AttachmentId(self::ATTACHMENT))->name);
    }

    public function testAFileIsNotReadWithoutTheRightToManagePersonalData(): void
    {
        $this->expectException(PersonalDataManagersOnly::class);

        $this->withAnAttachment()->attachmentReadBy($this->colleagueWithoutTheRight(), new AttachmentId(self::ATTACHMENT));
    }

    public function testNothingIsToMakeConformWhileItsContactIsKnown(): void
    {
        $exchange = $this->withAnAttachment();

        $this->assertNotContains(ExchangeIntent::MakeConform, $this->possibleIntentsOf($exchange));
        $this->assertFalse($exchange->isToMakeConform());
        $this->assertFalse(StateOf::the($exchange)['contactForgotten']);
    }

    public function testForgettingItsContactRecordsItAndLeavesItToMakeConform(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertTrue(StateOf::the($exchange)['contactForgotten']);
        $this->assertSame([true, true, true], [$exchange->isToMakeConform(), self::isTextToMakeConform($exchange), ExchangeConformity::areAttachmentsToMakeConform(StateOf::the($exchange)['contactForgotten'])]);
        $this->assertEquals([new ExchangeContactForgotten(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testDeclaringItConformRecordsWhoDeclaredIt(): void
    {
        $exchange = $this->withItsContactForgotten();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');

        $exchange->declareConform(by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)['madeConform']);
        $this->assertEquals([new ExchangeMadeConform(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testDeclaredConformItsTextIsNoLongerToMakeConformButItsFilesStay(): void
    {
        $exchange = $this->withItsContactForgotten();

        $exchange->declareConform(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame([false, true], [self::isTextToMakeConform($exchange), $exchange->isToMakeConform()]);
        $this->assertNotContains(ExchangeIntent::MakeConform, $this->possibleIntentsOf($exchange));
    }

    public function testDeclaredConformAndItsFilesRemovedNothingIsToMakeConform(): void
    {
        $exchange = $this->withItsContactForgotten();

        $exchange->declareConform(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
        $exchange->removeAttachment(new AttachmentId(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertFalse($exchange->isToMakeConform());
    }

    public function testAnExchangeWithoutTextIsToMakeConformUntilDeclaredSo(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame([true, false], [$exchange->isToMakeConform(), self::isTextToMakeConform($exchange)]);
    }

    public function testAnExchangeWhoseContactIsKnownIsNotDeclaredConform(): void
    {
        $this->expectException(NothingToMakeConform::class);

        $this->anExchange()->declareConform(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAFullExchangeOffersNoRoomForAnotherFile(): void
    {
        $this->assertNotContains(ExchangeIntent::AttachFile, $this->possibleIntentsOf($this->aFullExchange()));
    }

    public function testAFullExchangeRefusesAnotherFile(): void
    {
        $this->expectException(NoRoomForAnAttachment::class);

        $this->aFullExchange()->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAttachingRecordsIt(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();

        $exchange->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new FileAttached(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testRemovingAnAttachmentRecordsIt(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->removeAttachment(new AttachmentId(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame([], $exchange->attachments());
        $this->assertEquals([new AttachmentRemoved(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), self::FILE, new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    /**
     * Every intention is refused exactly when the exchange does not offer it.
     */
    #[DataProvider('intentionsStatesAndMembers')]
    public function testAnIntentionIsRefusedExactlyWhenNotOffered(ExchangeIntent $intent, string $state, string $member): void
    {
        $exchange = $this->{$state}();
        $prospector = $this->{$member}();
        $offered = in_array($intent, $this->possibleIntentsOf($exchange, $prospector), true);

        try {
            $this->carryOut($intent, $exchange, $prospector->id);
            $refused = false;
        } catch (NoRoomForAnAttachment|NothingToMakeConform|NotPossibleInItsState|NotTheHolder) {
            $refused = true;
        }

        $this->assertSame(!$offered, $refused);
    }

    /**
     * @return iterable<string, array{ExchangeIntent, string, string}>
     */
    public static function intentionsStatesAndMembers(): iterable
    {
        foreach (ExchangeIntent::cases() as $intent) {
            foreach (['withAnAttachment', 'withItsContactForgotten', 'plannedWithAnAttachment', 'due', 'cancelled'] as $state) {
                foreach (['me', 'colleagueWithoutTheRight'] as $member) {
                    yield sprintf('%s %s by %s', $intent->name, $state, $member) => [$intent, $state, $member];
                }
            }
        }
    }

    /**
     * What the policy offers a member now given the state of the exchange, as its reading tells it (ADR 0088).
     *
     * @return list<ExchangeIntent>
     */
    private function possibleIntentsOf(Exchange $exchange, ?Prospector $member = null): array
    {
        $state = StateOf::the($exchange);
        $awaitsBeingMadeConform = ExchangeConformity::awaitsBeingMadeConform($state['contactForgotten'], null !== $state['madeConform']);
        $due = $state['facts']->occurredAt <= new \DateTimeImmutable(self::NOW);

        return ExchangePossibilities::given(new ExchangeSituation(count($exchange->attachments()), $awaitsBeingMadeConform, $state['status'], $due, $state['createdBy']->equals(($member ?? $this->me())->id), $state['facts']->channel));
    }

    /**
     * As the reading of the exchange tells it (ADR 0088).
     */
    private static function isTextToMakeConform(Exchange $exchange): bool
    {
        $state = StateOf::the($exchange);

        return ExchangeConformity::isTextToMakeConform(null !== $state['facts']->text, $state['contactForgotten'], null !== $state['madeConform']);
    }

    private function carryOut(ExchangeIntent $intent, Exchange $exchange, MemberId $by): void
    {
        $now = new \DateTimeImmutable(self::NOW);

        match ($intent) {
            ExchangeIntent::CorrectExchange => $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, $now), ExchangeStatus::Done, by: $by, at: $now),
            ExchangeIntent::CorrectText => $exchange->correctText('Rappellera lundi', by: $by, at: $now),
            ExchangeIntent::CorrectVenue => $exchange->correctVenue('Café de Flore', null, by: $by, at: $now),
            ExchangeIntent::MakeConform => $exchange->declareConform(by: $by, at: $now),
            ExchangeIntent::EraseExchange => $exchange->erase(by: $by, at: $now),
            ExchangeIntent::AttachFile => $exchange->attach($this->anAttachment('0190a1c0-0000-7000-8000-000000000008'), by: $by, at: $now),
            ExchangeIntent::RemoveAttachment => $exchange->removeAttachment(new AttachmentId(self::ATTACHMENT), by: $by, at: $now),
            ExchangeIntent::MarkExchangeDone => $exchange->markDone(StateOf::the($exchange)['facts']->occurredAt, 'Compte rendu', by: $by, at: $now),
            ExchangeIntent::MarkExchangeMissed => $exchange->markMissed(by: $by, at: $now),
            ExchangeIntent::MoveExchange => $exchange->moveTo($now->modify('+2 days'), by: $by, at: $now),
            ExchangeIntent::CancelExchange => $exchange->cancel(null, by: $by, at: $now),
        };
    }

    public function testCorrectingRecordsItAndWhoCorrected(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW), 'Objet : mission');

        $exchange->correct($corrected, ExchangeStatus::Done, by: $this->me()->id, at: $later);

        $this->assertEquals($corrected, StateOf::the($exchange)['facts']);
        $this->assertEquals(new Correction($this->me()->id, $later), StateOf::the($exchange)['lastCorrection']);
        $this->assertEquals([new ExchangeCorrected(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testANotedExchangeIsNotCorrected(): void
    {
        $this->assertNull(StateOf::the($this->anExchange())['lastCorrection']);
    }

    public function testACorrectionIsNeverInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)->modify('+1 second')), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testCorrectingTheTextKeepsTheOtherFactsAndRecordsWhoCorrected(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');

        $exchange->correctText('La contactée est intéressée', by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'La contactée est intéressée'), StateOf::the($exchange)['facts']);
        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)['lastCorrection']);
        $this->assertEquals([new ExchangeTextCorrected(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testTheTextIsFlaggedToAProspectorWithoutTheRightToManagePersonalData(): void
    {
        $this->assertTrue($this->anExchange()->isTextFlaggedFor($this->colleagueWithoutTheRight()));
    }

    public function testTheTextIsNotFlaggedToAManagerOfPersonalData(): void
    {
        $this->assertFalse($this->anExchange()->isTextFlaggedFor($this->me()));
    }

    public function testNoTextIsNoTextToFlag(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->assertFalse($exchange->isTextFlaggedFor($this->colleagueWithoutTheRight()));
    }

    public function testErasingRecordsItWithTheFilesLeftToRemove(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->erase(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), [self::FILE], new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    private function withAnAttachment(): Exchange
    {
        $exchange = $this->anExchange();
        $exchange->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        return $exchange;
    }

    private function withItsContactForgotten(): Exchange
    {
        $exchange = $this->withAnAttachment();
        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        return $exchange;
    }

    private function aFullExchange(): Exchange
    {
        $exchange = $this->anExchange();

        foreach (range(1, Exchange::MOST_ATTACHMENTS) as $number) {
            $exchange->attach($this->anAttachment(sprintf('0190a1c0-0000-7000-8000-%012d', 100 + $number)), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
        }

        return $exchange;
    }

    private function anAttachment(string $id): Attachment
    {
        return new Attachment(new AttachmentId($id), 'devis.pdf', 2048, 'application/pdf', self::FILE);
    }

    private function planned(string $ahead = '+1 day'): Exchange
    {
        $now = new \DateTimeImmutable(self::NOW);
        $facts = ExchangeFacts::planned(Channel::Meeting, $now->modify($ahead), Duration::inMinutes(45), null);

        return Exchange::plannedWithAContact(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new PersonId(self::CONTACT), [new OrganizationId(self::ACME)], $facts, $now);
    }

    private function plannedWithAnAttachment(): Exchange
    {
        $exchange = $this->planned();
        $exchange->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        return $exchange;
    }

    /**
     * Planned the day before for an hour ago, with a file: its time has come.
     */
    private function due(): Exchange
    {
        $now = new \DateTimeImmutable(self::NOW);
        $facts = ExchangeFacts::planned(Channel::Meeting, $now->modify('-1 hour'), Duration::inMinutes(45), 'Ordre du jour');
        $exchange = Exchange::plannedWithAContact(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new PersonId(self::CONTACT), [new OrganizationId(self::ACME)], $facts, $now->modify('-1 day'));
        $exchange->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: $now);

        return $exchange;
    }

    private function cancelled(): Exchange
    {
        $exchange = $this->plannedWithAnAttachment();
        $exchange->cancel(null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        return $exchange;
    }

    private function anExchange(): Exchange
    {
        return $this->noted(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'Intéressée'));
    }

    private function noted(ExchangeFacts $facts, ExchangeStatus $outcome = ExchangeStatus::Done): Exchange
    {
        return Exchange::notedWithAContact(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new PersonId(self::CONTACT), [new OrganizationId(self::ACME), new OrganizationId(self::GROUP)], $facts, $outcome, new \DateTimeImmutable(self::NOW));
    }

    private function me(): Prospector
    {
        return new Prospector(new MemberId(self::MINE), new AccountId(self::MINE), true);
    }

    private function colleagueWithoutTheRight(): Prospector
    {
        return new Prospector(new MemberId(self::COLLEAGUE), new AccountId(self::MINE));
    }
}
