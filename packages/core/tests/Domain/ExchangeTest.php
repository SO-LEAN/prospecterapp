<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCancelled;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeJoinedCampaign;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedDone;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedMissed;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeRedacted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeVenueCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Event\VideoCallLinkReceived;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\InvalidVideoCallLink;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToRedact;
use Prospecter\Core\Prospecting\Domain\Exception\NotTheHolder;
use Prospecter\Core\Prospecting\Domain\Exception\NotUpcoming;
use Prospecter\Core\Prospecting\Domain\Exception\NotYetDue;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;
use Prospecter\Core\Prospecting\Domain\Exception\PlannedExchangeInThePast;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\RecipientCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
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
    private const string CAMPAIGN = '0190a1c0-0000-7000-8000-000000000008';
    private const string FILE = '6f1c2a9e0b7d4c3a8e5f1b2c3d4e5f60';
    private const string NOW = '2026-10-18 19:00';
    private const string LATER = '2026-10-18 19:05';
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
        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)->object('status', ExchangeStatus::class));
    }

    public function testANotedExchangeIsDone(): void
    {
        $this->assertSame(ExchangeStatus::Done, StateOf::the($this->anExchange())->object('status', ExchangeStatus::class));
    }

    public function testAnExchangeAMomentAheadIsPlanned(): void
    {
        $this->assertSame(ExchangeStatus::Planned, StateOf::the($this->planned('+1 second'))->object('status', ExchangeStatus::class));
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

        $this->assertSame(45, StateOf::the($exchange)->object('facts', ExchangeFacts::class)->duration?->minutes);
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
            $status = StateOf::the($this->noted(new ExchangeFacts($channel, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Missed))->object('status', ExchangeStatus::class);
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

        $this->assertSame(ExchangeStatus::Done, StateOf::the($exchange)->object('status', ExchangeStatus::class));
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

        $this->assertEquals([ExchangeStatus::Done, $tookPlaceAt, 'Mission en mars', 45], [StateOf::the($exchange)->object('status', ExchangeStatus::class), StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt, StateOf::the($exchange)->object('facts', ExchangeFacts::class)->text, StateOf::the($exchange)->object('facts', ExchangeFacts::class)->duration?->minutes]);
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

        $this->assertSame(ExchangeStatus::Missed, StateOf::the($exchange)->object('status', ExchangeStatus::class));
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
        $itsTime = StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt;

        $exchange->markMissed(by: $this->me()->id, at: $itsTime);

        $this->assertSame(ExchangeStatus::Missed, StateOf::the($exchange)->object('status', ExchangeStatus::class));
    }

    public function testForgettingItsContactAtTheMomentOfAPlannedExchangeLeavesItToItsHolder(): void
    {
        $exchange = $this->planned();

        $exchange->forgetItsContact(by: $this->me()->id, at: StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt);

        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)->object('status', ExchangeStatus::class));
    }

    public function testMovedItKeepsItsDuration(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+2 days');

        $exchange->moveTo($later, null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([$later, 45], [StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt, StateOf::the($exchange)->object('facts', ExchangeFacts::class)->duration?->minutes]);
        $this->assertEquals([new ExchangeMoved(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testMovedForAnotherDurationItLastsIt(): void
    {
        $exchange = $this->planned();
        $later = new \DateTimeImmutable(self::NOW)->modify('+2 days');

        $exchange->moveTo($later, Duration::inMinutes(90), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(90, StateOf::the($exchange)->object('facts', ExchangeFacts::class)->duration?->minutes);
    }

    public function testAnExchangeIsNotMovedIntoThePast(): void
    {
        $this->expectException(PlannedExchangeInThePast::class);

        $this->planned()->moveTo(new \DateTimeImmutable(self::NOW), null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testCancellingRecordsWhoCalledItOff(): void
    {
        $exchange = $this->planned();
        $exchange->releaseEvents();

        $exchange->cancel(Canceller::Contact, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Cancelled, StateOf::the($exchange)->object('status', ExchangeStatus::class));
        $this->assertEquals(new Cancellation($this->me()->id, new \DateTimeImmutable(self::NOW), Canceller::Contact), StateOf::the($exchange)->objectOrNull('cancellation', Cancellation::class));
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

        $this->assertEquals(new Cancellation($this->colleagueWithoutTheRight()->id, new \DateTimeImmutable(self::NOW), null), StateOf::the($exchange)->objectOrNull('cancellation', Cancellation::class));
        $this->assertEquals([
            new ExchangeContactForgotten(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW)),
            new ExchangeCancelled(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW)),
        ], $exchange->releaseEvents());
    }

    public function testForgettingItsContactLeavesAPlannedExchangeWhoseTimeHasComeToItsHolder(): void
    {
        $exchange = $this->due();

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ExchangeStatus::Planned, StateOf::the($exchange)->object('status', ExchangeStatus::class));
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
        $this->assertSame('Café de Flore', ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable(self::NOW), Duration::inMinutes(null), null)->withVenue('Café de Flore', null)->place);
        $this->assertEquals(new VideoCallLink(self::MEET), ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable(self::NOW), Duration::inMinutes(null), null)->withVenue(null, new VideoCallLink(self::MEET))->link);
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

        $this->assertEquals(ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable(self::NOW)->modify('+1 day'), Duration::inMinutes(45), null)->withVenue('Les Deux Magots', null), StateOf::the($exchange)->object('facts', ExchangeFacts::class));
        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)->objectOrNull('lastCorrection', Correction::class));
        $this->assertEquals([new ExchangeVenueCorrected(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testACorrectionOfTheWholeKeepsThePlaceWhileItStaysAMeeting(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: 'Café de Flore'));

        $exchange->correct(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), 'Déjeuner'), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame('Café de Flore', StateOf::the($exchange)->object('facts', ExchangeFacts::class)->place);
    }

    public function testACorrectionOfTheWholeDropsThePlaceOfAMeetingNoLongerOne(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW), place: 'Café de Flore'));

        $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertNull(StateOf::the($exchange)->object('facts', ExchangeFacts::class)->place);
    }

    public function testACorrectionOfTheWholeKeepsTheLinkWhileItStaysAVideoCall(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::VideoCall, Direction::Outbound, new \DateTimeImmutable(self::NOW), link: new VideoCallLink(self::MEET)));

        $exchange->correct(new ExchangeFacts(Channel::VideoCall, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals(new VideoCallLink(self::MEET), StateOf::the($exchange)->object('facts', ExchangeFacts::class)->link);
    }

    public function testALinkGivenEndsTheRequestOfOne(): void
    {
        $this->assertFalse(ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable(self::NOW), Duration::inMinutes(null), null)->withVenue(null, new VideoCallLink(self::MEET))->askingALink()->linkRequested);
    }

    public function testCorrectingItsTextKeepsTheRequestOfALink(): void
    {
        $exchange = $this->aVideoCallAskingItsLink();

        $exchange->correctText('Ordre du jour', by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertTrue(StateOf::the($exchange)->object('facts', ExchangeFacts::class)->linkRequested);
    }

    public function testAVideoCallAskingItsLinkReceivesItWithoutAnAuthor(): void
    {
        $exchange = $this->aVideoCallAskingItsLink();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 minute');

        $exchange->receiveLink(new VideoCallLink(self::MEET), at: $later);

        $facts = StateOf::the($exchange)->object('facts', ExchangeFacts::class);
        $this->assertEquals([new VideoCallLink(self::MEET), false], [$facts->link, $facts->linkRequested]);
        $this->assertNull(StateOf::the($exchange)->objectOrNull('lastCorrection', Correction::class));
        $this->assertEquals([new VideoCallLinkReceived(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testALinkGivenMeanwhileWinsOverTheLinkReceived(): void
    {
        $exchange = $this->aVideoCallAskingItsLink();
        $exchange->correctVenue(null, new VideoCallLink('https://teams.microsoft.com/l/meetup-join/xyz'), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
        $exchange->releaseEvents();

        $exchange->receiveLink(new VideoCallLink(self::MEET), at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals(new VideoCallLink('https://teams.microsoft.com/l/meetup-join/xyz'), StateOf::the($exchange)->object('facts', ExchangeFacts::class)->link);
        $this->assertSame([], $exchange->releaseEvents());
    }

    public function testALinkRemovedIsNotReceivedAgain(): void
    {
        $exchange = $this->aVideoCallAskingItsLink();
        $exchange->receiveLink(new VideoCallLink(self::MEET), at: new \DateTimeImmutable(self::NOW));
        $exchange->correctVenue(null, null, by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $exchange->receiveLink(new VideoCallLink(self::MEET), at: new \DateTimeImmutable(self::NOW));

        $this->assertNull(StateOf::the($exchange)->object('facts', ExchangeFacts::class)->link);
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

        $this->assertEquals([new OrganizationId(self::GROUP)], StateOf::the($exchange)->listOf('keptFor', OrganizationId::class));
        $this->assertTrue($exchange->isKeptForAnOrganization());
        $this->assertEquals([new ExchangeNoLongerKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testItsContactToAttachAttachedItIsKeptForTheirOrganization(): void
    {
        $exchange = Exchange::noted(new Gesture(new AccountId(self::MINE), $this->me()->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::EXCHANGE), new ContactCorrespondent(new ContactId(self::CONTACT), []), Noting::of(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done));
        $exchange->releaseEvents();
        $this->assertEquals(new ContactId(self::CONTACT), $exchange->contactToAttach());

        $exchange->keepFor(new OrganizationId(self::ACME), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertNull($exchange->contactToAttach());
        $this->assertEquals([new ExchangeKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
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
        $this->assertFalse(StateOf::the($exchange)->bool('contactForgotten'));
    }

    public function testForgettingItsContactRecordsItAndLeavesItToMakeConform(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertTrue(StateOf::the($exchange)->bool('contactForgotten'));
        $this->assertSame([true, true, true], [$exchange->isToMakeConform(), self::isTextToMakeConform($exchange), ExchangeConformity::areAttachmentsToMakeConform(StateOf::the($exchange)->bool('contactForgotten'))]);
        $this->assertEquals([new ExchangeContactForgotten(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testDeclaringItConformRecordsWhoDeclaredIt(): void
    {
        $exchange = $this->withItsContactForgotten();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');

        $exchange->declareConform(by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)->objectOrNull('madeConform', Correction::class));
        $this->assertEquals([new ExchangeRedacted(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
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
        $this->expectException(NothingToRedact::class);

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

    public function testImportingAnAttachmentRecordsItWithItsFileToKeep(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();

        $exchange->importAttachment($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new AttachmentImported(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), self::FILE, new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
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
        if (!$exchange instanceof Exchange || !$prospector instanceof Prospector) {
            $this->fail(sprintf('%s is no exchange or %s no prospector', $state, $member));
        }
        $offered = in_array($intent, $this->possibleIntentsOf($exchange, $prospector), true);

        try {
            $this->carryOut($intent, $exchange, $prospector->id);
            $refused = false;
        } catch (NoRoomForAnAttachment|NothingToRedact|NotPossibleInItsState|NotTheHolder) {
            $refused = true;
        }

        $this->assertSame(!$offered, $refused);
    }

    /**
     * Moving back moves the exchange to the moment it was just moved from. Only the exchange's reading after that
     * move offers it, because the aggregate does not know that moment.
     *
     * @return iterable<string, array{ExchangeIntent, string, string}>
     */
    public static function intentionsStatesAndMembers(): iterable
    {
        foreach (array_filter(ExchangeIntent::cases(), static fn (ExchangeIntent $intent): bool => ExchangeIntent::MoveExchangeBack !== $intent) as $intent) {
            foreach (['withAnAttachment', 'withItsContactForgotten', 'plannedWithAnAttachment', 'due', 'cancelled'] as $state) {
                foreach (['me', 'colleagueWithoutTheRight'] as $member) {
                    yield sprintf('%s %s by %s', $intent->name, $state, $member) => [$intent, $state, $member];
                }
            }
        }
    }

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function holdersAndFormerMoments(): iterable
    {
        yield 'its holder, its former moment to come' => [true, true, true];
        yield 'its holder, its former moment passed' => [true, false, false];
        yield 'a colleague, its former moment to come' => [false, true, false];
    }

    #[DataProvider('holdersAndFormerMoments')]
    public function testAnExchangeJustMovedIsMovedBackByItsHolderWhileItsFormerMomentIsToCome(bool $heldByTheMember, bool $formerMomentToCome, bool $offered): void
    {
        $situation = new ExchangeSituation(0, false, ExchangeStatus::Planned, false, $heldByTheMember, Channel::VideoCall, $formerMomentToCome);

        $this->assertSame($offered, in_array(ExchangeIntent::MoveExchangeBack, ExchangePossibilities::given($situation), true));
    }

    public function testAMomentIsToComeOnlyAfterNow(): void
    {
        $now = new \DateTimeImmutable(self::NOW);

        $this->assertTrue(Exchange::isAMomentToCome($now->modify('+1 second'), $now));
        $this->assertFalse(Exchange::isAMomentToCome($now, $now));
    }

    /**
     * What the policy offers a member now, given the exchange's state as its reading tells it (ADR 0088).
     *
     * @return list<ExchangeIntent>
     */
    private function possibleIntentsOf(Exchange $exchange, ?Prospector $member = null): array
    {
        $state = StateOf::the($exchange);
        $awaitsBeingMadeConform = ExchangeConformity::awaitsBeingMadeConform($state->bool('contactForgotten'), null !== $state->objectOrNull('madeConform', Correction::class));
        $due = $state->object('facts', ExchangeFacts::class)->occurredAt <= new \DateTimeImmutable(self::NOW);

        return ExchangePossibilities::given(new ExchangeSituation(count($exchange->attachments()), $awaitsBeingMadeConform, $state->object('status', ExchangeStatus::class), $due, $state->object('createdBy', MemberId::class)->equals(($member ?? $this->me())->id), $state->object('facts', ExchangeFacts::class)->channel));
    }

    /**
     * Tells whether the text still needs redacting, as the exchange's reading tells it (ADR 0088).
     */
    private static function isTextToMakeConform(Exchange $exchange): bool
    {
        $state = StateOf::the($exchange);

        return ExchangeConformity::isTextToMakeConform(null !== $state->object('facts', ExchangeFacts::class)->text, $state->bool('contactForgotten'), null !== $state->objectOrNull('madeConform', Correction::class));
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
            ExchangeIntent::MarkExchangeDone => $exchange->markDone(StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt, 'Compte rendu', by: $by, at: $now),
            ExchangeIntent::MarkExchangeMissed => $exchange->markMissed(by: $by, at: $now),
            ExchangeIntent::MoveExchange, ExchangeIntent::MoveExchangeBack => $exchange->moveTo($now->modify('+2 days'), null, by: $by, at: $now),
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

        $this->assertEquals($corrected, StateOf::the($exchange)->object('facts', ExchangeFacts::class));
        $this->assertEquals(new Correction($this->me()->id, $later), StateOf::the($exchange)->objectOrNull('lastCorrection', Correction::class));
        $this->assertEquals([new ExchangeCorrected(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testANotedExchangeIsNotCorrected(): void
    {
        $this->assertNull(StateOf::the($this->anExchange())->objectOrNull('lastCorrection', Correction::class));
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

        $this->assertEquals(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'La contactée est intéressée'), StateOf::the($exchange)->object('facts', ExchangeFacts::class));
        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), StateOf::the($exchange)->objectOrNull('lastCorrection', Correction::class));
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

    public function testErasingRecordsItWithTheFilesLeftToRemoveAndItsContact(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->erase(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), [self::FILE], new \DateTimeImmutable(self::NOW), new ContactId(self::CONTACT))], $exchange->releaseEvents());
    }

    public function testErasingAnExchangeOfACampaignNamesItsCampaign(): void
    {
        $recipient = new RecipientInCampaign(new CampaignId(self::CAMPAIGN), new ContactId(self::CONTACT), Channel::Email, running: true, recipient: true, pending: true);
        $noting = Noting::of(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)), ExchangeStatus::Done);
        $exchange = Exchange::noted(new Gesture(new AccountId(self::MINE), $this->me()->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::EXCHANGE), new RecipientCorrespondent($recipient, [new OrganizationId(self::ACME)]), $noting);
        $exchange->releaseEvents();

        $exchange->erase(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), [], new \DateTimeImmutable(self::NOW), new ContactId(self::CONTACT), new CampaignId(self::CAMPAIGN))], $exchange->releaseEvents());
    }

    public function testAnEmailSentJoinsTheCampaignOfItsRecipient(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)));
        $exchange->releaseEvents();

        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->colleagueWithoutTheRight()->id, at: new \DateTimeImmutable(self::LATER));

        $this->assertEquals([new ExchangeJoinedCampaign(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::LATER))], $exchange->releaseEvents());
        $this->assertEquals(new CampaignId(self::CAMPAIGN), StateOf::the($exchange)->object('campaign', CampaignId::class));
    }

    public function testWhoJoinsTheCampaignCorrectsTheExchange(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)));

        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->colleagueWithoutTheRight()->id, at: new \DateTimeImmutable(self::LATER));

        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, new \DateTimeImmutable(self::LATER)), StateOf::the($exchange)->objectOrNull('lastCorrection', Correction::class));
    }

    public function testTheEmailOfARecipientHandledAlreadyNeverJoinsTheCampaign(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)));

        $this->expectException(RecipientAlreadyProcessed::class);

        $exchange->joinCampaign(new RecipientInCampaign(new CampaignId(self::CAMPAIGN), new ContactId(self::CONTACT), Channel::Email, running: true, recipient: true, pending: false), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));
    }

    public function testAnExchangeOfACampaignNeverJoinsTheCampaign(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)));
        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));

        $this->expectExceptionObject(ExchangeAlreadyNoted::because('Exchange already noted', context: ['exchange' => self::EXCHANGE]));

        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));
    }

    public function testAnExchangeWithAnotherContactNeverJoinsTheCampaign(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW)));

        $this->expectException(ExchangeAlreadyNoted::class);

        $exchange->joinCampaign(new RecipientInCampaign(new CampaignId(self::CAMPAIGN), new ContactId(self::ACME), Channel::Email, running: true, recipient: true, pending: true), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));
    }

    public function testAnEmailReceivedNeverJoinsTheCampaign(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Email, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->expectException(ExchangeAlreadyNoted::class);

        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));
    }

    public function testAMessageOfAnotherChannelNeverJoinsTheCampaign(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::LinkedIn, Direction::Outbound, new \DateTimeImmutable(self::NOW)));

        $this->expectException(ExchangeAlreadyNoted::class);

        $exchange->joinCampaign($this->recipientInCampaign(), by: $this->me()->id, at: new \DateTimeImmutable(self::LATER));
    }

    public function testErasingAnExchangeWithItsOrganizationSaysSo(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));
        $exchange->releaseEvents();

        $exchange->eraseWithItsOrganization(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), [], new \DateTimeImmutable(self::NOW), new ContactId(self::CONTACT), withItsOrganization: true)], $exchange->releaseEvents());
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

        return Exchange::planned(new Gesture(new AccountId(self::MINE), $this->me()->id, $now), new ExchangeId(self::EXCHANGE), new ContactCorrespondent(new ContactId(self::CONTACT), [new OrganizationId(self::ACME)]), $facts);
    }

    private function aVideoCallAskingItsLink(): Exchange
    {
        $now = new \DateTimeImmutable(self::NOW);
        $facts = ExchangeFacts::planned(Channel::VideoCall, $now->modify('+1 day'), Duration::inMinutes(30), null)->askingALink();

        return Exchange::planned(new Gesture(new AccountId(self::MINE), $this->me()->id, $now), new ExchangeId(self::EXCHANGE), new ContactCorrespondent(new ContactId(self::CONTACT), [new OrganizationId(self::ACME)]), $facts);
    }

    private function plannedWithAnAttachment(): Exchange
    {
        $exchange = $this->planned();
        $exchange->attach($this->anAttachment(self::ATTACHMENT), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        return $exchange;
    }

    /**
     * The exchange was planned the day before for an hour ago, and it has a file. Its time has come.
     */
    private function due(): Exchange
    {
        $now = new \DateTimeImmutable(self::NOW);
        $facts = ExchangeFacts::planned(Channel::Meeting, $now->modify('-1 hour'), Duration::inMinutes(45), 'Ordre du jour');
        $exchange = Exchange::planned(new Gesture(new AccountId(self::MINE), $this->me()->id, $now->modify('-1 day')), new ExchangeId(self::EXCHANGE), new ContactCorrespondent(new ContactId(self::CONTACT), [new OrganizationId(self::ACME)]), $facts);
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
        return Exchange::noted(new Gesture(new AccountId(self::MINE), $this->me()->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::EXCHANGE), new ContactCorrespondent(new ContactId(self::CONTACT), [new OrganizationId(self::ACME), new OrganizationId(self::GROUP)]), Noting::of($facts, $outcome));
    }

    private function recipientInCampaign(): RecipientInCampaign
    {
        return new RecipientInCampaign(new CampaignId(self::CAMPAIGN), new ContactId(self::CONTACT), Channel::Email, running: true, recipient: true, pending: true);
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
