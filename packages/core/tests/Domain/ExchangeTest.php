<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMadeConform;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\MissedOnlyForACall;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToMakeConform;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

class ExchangeTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string COLLEAGUE = '0190a1c0-0000-7000-8000-000000000002';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000003';
    private const string CONTACT = '0190a1c0-0000-7000-8000-000000000004';
    private const string ACME = '0190a1c0-0000-7000-8000-000000000005';
    private const string GROUP = '0190a1c0-0000-7000-8000-000000000006';
    private const string ATTACHMENT = '0190a1c0-0000-7000-8000-000000000007';
    private const string NOW = '2026-10-18 19:00';

    public function testNotingRecordsIt(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Sms, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->assertEquals([new ExchangeNoted(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
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

    #[DataProvider('directions')]
    public function testACallMayBeMissedWhateverItsDirection(Direction $direction): void
    {
        $this->assertTrue(new ExchangeFacts(Channel::Call, $direction, new \DateTimeImmutable(self::NOW), missed: true)->missed);
    }

    /**
     * @return iterable<string, array{Direction}>
     */
    public static function directions(): iterable
    {
        foreach (Direction::cases() as $direction) {
            yield $direction->name => [$direction];
        }
    }

    #[DataProvider('channelsButTheCall')]
    public function testOnlyACallIsMissed(Channel $channel): void
    {
        $this->expectException(MissedOnlyForACall::class);

        new ExchangeFacts($channel, Direction::Outbound, new \DateTimeImmutable(self::NOW), missed: true);
    }

    /**
     * @return iterable<string, array{Channel}>
     */
    public static function channelsButTheCall(): iterable
    {
        foreach (Channel::cases() as $channel) {
            if (Channel::Call !== $channel) {
                yield $channel->name => [$channel];
            }
        }
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

        $this->assertEquals([new OrganizationId(self::GROUP)], $exchange->keptFor());
        $this->assertTrue($exchange->isKeptForAnOrganization());
        $this->assertEquals([new ExchangeNoLongerKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testKeptForNoOrganizationOnceTheLastIsGone(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
        $exchange->stopKeepingFor(new OrganizationId(self::GROUP), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertFalse($exchange->isKeptForAnOrganization());
    }

    public function testItsContactForgottenEverythingIsOffered(): void
    {
        $this->assertSame(ExchangeIntent::cases(), $this->withItsContactForgotten()->possibleIntents());
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

        $this->assertNotContains(ExchangeIntent::MakeConform, $exchange->possibleIntents());
        $this->assertFalse($exchange->isToMakeConform());
        $this->assertSame(ContactPresence::Present, $exchange->contactPresence());
    }

    public function testForgettingItsContactRecordsItAndLeavesItToMakeConform(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->releaseEvents();

        $exchange->forgetItsContact(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame(ContactPresence::Forgotten, $exchange->contactPresence());
        $this->assertSame([true, true, true], [$exchange->isToMakeConform(), $exchange->isTextToMakeConform(), $exchange->areAttachmentsToMakeConform()]);
        $this->assertEquals([new ExchangeContactForgotten(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testDeclaringItConformRecordsWhoDeclaredIt(): void
    {
        $exchange = $this->withItsContactForgotten();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');

        $exchange->declareConform(by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), $exchange->madeConform());
        $this->assertEquals([new ExchangeMadeConform(new AccountId(self::MINE), $this->colleagueWithoutTheRight()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testDeclaredConformItsTextIsNoLongerToMakeConformButItsFilesStay(): void
    {
        $exchange = $this->withItsContactForgotten();

        $exchange->declareConform(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertSame([false, true], [$exchange->isTextToMakeConform(), $exchange->isToMakeConform()]);
        $this->assertNotContains(ExchangeIntent::MakeConform, $exchange->possibleIntents());
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

        $this->assertSame([true, false], [$exchange->isToMakeConform(), $exchange->isTextToMakeConform()]);
    }

    public function testAnExchangeWhoseContactIsKnownIsNotDeclaredConform(): void
    {
        $this->expectException(NothingToMakeConform::class);

        $this->anExchange()->declareConform(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testAFullExchangeOffersNoRoomForAnotherFile(): void
    {
        $this->assertNotContains(ExchangeIntent::AttachFile, $this->aFullExchange()->possibleIntents());
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
        $this->assertEquals([new AttachmentRemoved(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    /**
     * Every intention is refused exactly when the exchange does not offer it.
     */
    #[DataProvider('intentionsAndStates')]
    public function testAnIntentionIsRefusedExactlyWhenNotOffered(ExchangeIntent $intent, string $state): void
    {
        $exchange = $this->{$state}();
        $offered = in_array($intent, $exchange->possibleIntents(), true);

        try {
            $this->carryOut($intent, $exchange, $this->me()->id);
            $refused = false;
        } catch (NoRoomForAnAttachment|NothingToMakeConform) {
            $refused = true;
        }

        $this->assertSame(!$offered, $refused);
    }

    /**
     * @return iterable<string, array{ExchangeIntent, string}>
     */
    public static function intentionsAndStates(): iterable
    {
        foreach (ExchangeIntent::cases() as $intent) {
            foreach (['withAnAttachment', 'withItsContactForgotten'] as $state) {
                yield sprintf('%s %s', $intent->name, $state) => [$intent, $state];
            }
        }
    }

    private function carryOut(ExchangeIntent $intent, Exchange $exchange, MemberId $by): void
    {
        $now = new \DateTimeImmutable(self::NOW);

        match ($intent) {
            ExchangeIntent::CorrectExchange => $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, $now), by: $by, at: $now),
            ExchangeIntent::CorrectText => $exchange->correctText('Rappellera lundi', by: $by, at: $now),
            ExchangeIntent::MakeConform => $exchange->declareConform(by: $by, at: $now),
            ExchangeIntent::EraseExchange => $exchange->erase(by: $by, at: $now),
            ExchangeIntent::AttachFile => $exchange->attach($this->anAttachment('0190a1c0-0000-7000-8000-000000000008'), by: $by, at: $now),
            ExchangeIntent::RemoveAttachment => $exchange->removeAttachment(new AttachmentId(self::ATTACHMENT), by: $by, at: $now),
        };
    }

    public function testCorrectingRecordsItAndWhoCorrected(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW), 'Objet : mission');

        $exchange->correct($corrected, by: $this->me()->id, at: $later);

        $this->assertSame($corrected, $exchange->facts());
        $this->assertEquals(new Correction($this->me()->id, $later), $exchange->lastCorrection());
        $this->assertEquals([new ExchangeCorrected(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testANotedExchangeIsNotCorrected(): void
    {
        $this->assertNull($this->anExchange()->lastCorrection());
    }

    public function testACorrectionIsNeverInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)->modify('+1 second')), by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));
    }

    public function testCorrectingTheTextKeepsTheOtherFactsAndRecordsWhoCorrected(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');

        $exchange->correctText('La contactée est intéressée', by: $this->colleagueWithoutTheRight()->id, at: $later);

        $this->assertEquals(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'La contactée est intéressée'), $exchange->facts());
        $this->assertEquals(new Correction($this->colleagueWithoutTheRight()->id, $later), $exchange->lastCorrection());
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

    public function testErasingRecordsIt(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();

        $exchange->erase(by: $this->me()->id, at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
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
        return new Attachment(new AttachmentId($id), 'devis.pdf', 2048, 'application/pdf', bin2hex(random_bytes(16)));
    }

    private function anExchange(): Exchange
    {
        return $this->noted(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'Intéressée'));
    }

    private function noted(ExchangeFacts $facts): Exchange
    {
        return Exchange::notedWithAContact(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new PersonId(self::CONTACT), [new OrganizationId(self::ACME), new OrganizationId(self::GROUP)], $facts, new \DateTimeImmutable(self::NOW));
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
