<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Event\CampaignDiscarded;
use Prospecter\Core\Campaigns\Domain\Event\CampaignFrozen;
use Prospecter\Core\Campaigns\Domain\Event\CampaignPrepared;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRecipientsChanged;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRevised;
use Prospecter\Core\Campaigns\Domain\Event\CampaignStarted;
use Prospecter\Core\Campaigns\Domain\Event\CampaignStopped;
use Prospecter\Core\Campaigns\Domain\Event\RecipientDismissed;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Double\StateOf;

/**
 * Every intention of a campaign is refused exactly when its state does not offer it, and records its event.
 */
final class CampaignTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string ME = '0190a1c0-0000-7000-8000-000000000002';
    private const string CAMPAIGN = '0190a1c0-0000-7000-8000-000000000003';
    private const string ADA = '0190a1c0-0000-7000-8000-000000000004';
    private const string ALAN = '0190a1c0-0000-7000-8000-000000000005';
    private const string NOW = '2026-11-17 10:00';

    public function testPreparingRecordsIt(): void
    {
        $this->assertEquals([new CampaignPrepared(new AccountId(self::ACCOUNT), new MemberId(self::ME), new CampaignId(self::CAMPAIGN), $this->now())], $this->prepared()->releaseEvents());
    }

    public function testAPreparedCampaignIsADraft(): void
    {
        $this->assertSame(CampaignStatus::Draft, StateOf::the($this->prepared())['status']);
    }

    public function testADraftIsRevisedDiscardedAndFrozen(): void
    {
        $this->assertSame([CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::FreezeCampaign], CampaignPossibilities::given(CampaignStatus::Draft, hasRecipients: false));
    }

    public function testAFrozenCampaignChangesItsRecipientsIsStillRevisedAndDiscardedAndStarts(): void
    {
        $this->assertSame([CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::AddRecipients, CampaignIntent::RemoveRecipient, CampaignIntent::StartCampaign], CampaignPossibilities::given(CampaignStatus::Frozen, hasRecipients: true));
    }

    public function testAFrozenCampaignItsErasedContactsEmptiedNoLongerStarts(): void
    {
        $this->assertSame([CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::AddRecipients, CampaignIntent::RemoveRecipient], CampaignPossibilities::given(CampaignStatus::Frozen, hasRecipients: false));
    }

    public function testStartingAFrozenCampaignItsErasedContactsEmptiedIsRefusedForWantOfRecipient(): void
    {
        $campaign = $this->in(CampaignStatus::Frozen);
        $campaign->forget(new PersonId(self::ADA));
        $campaign->forget(new PersonId(self::ALAN));

        $this->expectExceptionObject(CampaignWithoutRecipient::left());
        $campaign->start(new MemberId(self::ME), $this->now());
    }

    public function testAStartedCampaignIsRunAndStopped(): void
    {
        $this->assertSame([CampaignIntent::RunCampaign, CampaignIntent::StopCampaign], CampaignPossibilities::given(CampaignStatus::Started, hasRecipients: true));
    }

    public function testAStoppedCampaignOffersNothing(): void
    {
        $this->assertSame([], CampaignPossibilities::given(CampaignStatus::Stopped, hasRecipients: true));
    }

    public function testAnEndedCampaignOffersNothing(): void
    {
        $this->assertSame([], CampaignPossibilities::given(CampaignStatus::Ended, hasRecipients: true));
    }

    public function testDismissingTheLastRecipientToHandleEndsTheCampaign(): void
    {
        $campaign = $this->in(CampaignStatus::Started);
        $campaign->process(new PersonId(self::ADA), missed: true, at: $this->now());

        $campaign->dismiss(new PersonId(self::ALAN), 'Parti', new MemberId(self::ME), $this->later());

        $this->assertEquals([CampaignStatus::Ended, $this->later()], [StateOf::the($campaign)['status'], StateOf::the($campaign)['endedAt']]);
    }

    public function testProcessingARecipientHandledAlreadyChangesNothing(): void
    {
        $campaign = $this->in(CampaignStatus::Started);
        $campaign->dismiss(new PersonId(self::ADA), null, new MemberId(self::ME), $this->now());

        $campaign->process(new PersonId(self::ADA), missed: true, at: $this->now());

        $this->assertEquals([new Recipient(new PersonId(self::ADA), RecipientStatus::Dismissed), Recipient::pending(new PersonId(self::ALAN))], StateOf::the($campaign)['recipients']->recipients);
    }

    public function testAStoppedCampaignProcessesNobody(): void
    {
        $campaign = $this->in(CampaignStatus::Stopped);

        $campaign->process(new PersonId(self::ADA), missed: false, at: $this->now());

        $this->assertTrue(StateOf::the($campaign)['recipients']->recipients[0]->isPending());
    }

    #[DataProvider('offered')]
    public function testAnIntentOfferedRecordsItsEvent(CampaignStatus $status, CampaignIntent $intent, string $event): void
    {
        $campaign = $this->in($status);
        $campaign->releaseEvents();

        $this->exercise($campaign, $intent);

        $expected = RecipientDismissed::class === $event
            ? new RecipientDismissed(new AccountId(self::ACCOUNT), new MemberId(self::ME), new CampaignId(self::CAMPAIGN), new PersonId(self::ADA), $this->now())
            : new $event(new AccountId(self::ACCOUNT), new MemberId(self::ME), new CampaignId(self::CAMPAIGN), $this->now());

        $this->assertEquals([$expected], $campaign->releaseEvents());
    }

    /**
     * @return iterable<string, array{CampaignStatus, CampaignIntent, class-string}>
     */
    public static function offered(): iterable
    {
        $events = [
            CampaignIntent::ReviseCampaign->name => CampaignRevised::class,
            CampaignIntent::DiscardCampaign->name => CampaignDiscarded::class,
            CampaignIntent::FreezeCampaign->name => CampaignFrozen::class,
            CampaignIntent::AddRecipients->name => CampaignRecipientsChanged::class,
            CampaignIntent::RemoveRecipient->name => CampaignRecipientsChanged::class,
            CampaignIntent::StartCampaign->name => CampaignStarted::class,
            CampaignIntent::StopCampaign->name => CampaignStopped::class,
            CampaignIntent::RunCampaign->name => RecipientDismissed::class,
        ];

        foreach (CampaignStatus::cases() as $status) {
            foreach (CampaignPossibilities::given($status, hasRecipients: CampaignStatus::Draft !== $status) as $intent) {
                yield $status->value.' '.$intent->name => [$status, $intent, $events[$intent->name]];
            }
        }
    }

    #[DataProvider('notOffered')]
    public function testAnIntentNotOfferedIsRefused(CampaignStatus $status, CampaignIntent $intent): void
    {
        $campaign = $this->in($status);

        $this->expectException(NotPossibleInItsState::class);
        $this->exercise($campaign, $intent);
    }

    /**
     * @return iterable<string, array{CampaignStatus, CampaignIntent}>
     */
    public static function notOffered(): iterable
    {
        foreach (CampaignStatus::cases() as $status) {
            foreach (array_udiff(CampaignIntent::cases(), CampaignPossibilities::given($status, hasRecipients: CampaignStatus::Draft !== $status), static fn (CampaignIntent $one, CampaignIntent $other): int => strcmp($one->name, $other->name)) as $intent) {
                yield $status->value.' '.$intent->name => [$status, $intent];
            }
        }
    }

    public function testRevisingChangesEverythingButItsAuthor(): void
    {
        $campaign = $this->prepared();
        $targeting = Targeting::by(Channel::LinkedIn, ['#ESN', '#PHP']);
        $message = MessageTemplate::of('Salut {prénom}', null, Channel::LinkedIn);
        $campaign->revise(CampaignName::of('Relance PHP'), $targeting, $message, new MemberId('0190a1c0-0000-7000-8000-000000000009'), $this->now());
        $state = StateOf::the($campaign);

        $this->assertEquals([CampaignName::of('Relance PHP'), $targeting, $message, new MemberId(self::ME)], [$state['name'], $state['targeting'], $state['message'], $state['createdBy']]);
    }

    public function testFrozenItsSearchIsTheSameWhateverTheOrderOfItsTags(): void
    {
        $campaign = $this->prepared();
        $campaign->revise(CampaignName::of('Relance'), Targeting::by(Channel::Email, ['#ESN', '#PHP']), MessageTemplate::of('Bonjour', null, Channel::Email), new MemberId(self::ME), $this->now());
        $campaign->freeze([self::ADA => $this->reachedByEmail('#ESN', '#PHP')], new MemberId(self::ME), $this->now());

        $campaign->revise(CampaignName::of('Relance PHP'), Targeting::by(Channel::Email, ['#php', '#ESN']), MessageTemplate::of('Salut', null, Channel::Email), new MemberId(self::ME), $this->now());

        $this->assertEquals(CampaignName::of('Relance PHP'), StateOf::the($campaign)['name']);
    }

    public function testFreezingKeepsOnlyTheContactsItsSearchFinds(): void
    {
        $campaign = $this->prepared();

        $campaign->freeze([self::ADA => $this->reachedByEmail('#ESN'), self::ALAN => $this->reachedByEmail('#PHP')], new MemberId(self::ME), $this->now());

        $this->assertTrue($campaign->hasRecipient(new PersonId(self::ADA)));
        $this->assertFalse($campaign->hasRecipient(new PersonId(self::ALAN)));
    }

    private function exercise(Campaign $campaign, CampaignIntent $intent): void
    {
        $me = new MemberId(self::ME);

        match ($intent) {
            CampaignIntent::ReviseCampaign => $campaign->revise(CampaignName::of('Relance'), Targeting::by(Channel::Email, ['#ESN']), MessageTemplate::of('Bonjour', null, Channel::Email), $me, $this->now()),
            CampaignIntent::DiscardCampaign => $campaign->discard($me, $this->now()),
            CampaignIntent::FreezeCampaign => $campaign->freeze([self::ADA => $this->reachedByEmail('#ESN')], $me, $this->now()),
            CampaignIntent::AddRecipients => $campaign->addRecipient(new PersonId(self::ALAN), $this->reachedByEmail(), new Addressee('Alan', 'Turing', null, null), $me, $this->now()),
            CampaignIntent::RemoveRecipient => $campaign->removeRecipient(new PersonId(self::ADA), $me, $this->now()),
            CampaignIntent::StartCampaign => $campaign->start($me, $this->now()),
            CampaignIntent::StopCampaign => $campaign->stop($me, $this->now()),
            CampaignIntent::RunCampaign => $campaign->dismiss(new PersonId(self::ADA), null, $me, $this->now()),
        };
    }

    /**
     * A frozen campaign keeps Ada and Alan, so that one of them may be taken off.
     */
    private function in(CampaignStatus $status): Campaign
    {
        $campaign = $this->prepared();
        $me = new MemberId(self::ME);

        if (CampaignStatus::Draft !== $status) {
            $campaign->freeze([self::ADA => $this->reachedByEmail('#ESN'), self::ALAN => $this->reachedByEmail('#ESN')], $me, $this->now());
        }

        if ($status->hasStarted()) {
            $campaign->start($me, $this->now());
        }

        if (CampaignStatus::Stopped === $status) {
            $campaign->stop($me, $this->now());
        }

        if (CampaignStatus::Ended === $status) {
            $campaign->dismiss(new PersonId(self::ADA), null, $me, $this->now());
            $campaign->dismiss(new PersonId(self::ALAN), null, $me, $this->now());
        }

        return $campaign;
    }

    private function reachedByEmail(string ...$own): ContactTags
    {
        return ContactTags::of(array_map(Tag::written(...), array_values($own)), [], new Reach(true, false, false, false));
    }

    private function prepared(): Campaign
    {
        return Campaign::prepare(
            new CampaignId(self::CAMPAIGN),
            new AccountId(self::ACCOUNT),
            CampaignName::of('Relance ESN'),
            Targeting::by(Channel::Email, ['#ESN']),
            MessageTemplate::of('Bonjour {prénom}', 'Une question', Channel::Email),
            new MemberId(self::ME),
            $this->now(),
        );
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }

    private function later(): \DateTimeImmutable
    {
        return $this->now()->modify('+1 hour');
    }
}
