<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\ExchangeKeys;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The retriever builds the detail from the item the repository wrote, as its reader may read it, with what is
 * to make conform and what its state offers: the intentions its state forbids are absent, the exchange refusing
 * them (ADR 0078, 0079, 0088).
 */
class DynamoExchangeDetailRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';

    private Prospector $me;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
    }

    public function testWithTheRightToManagePersonalDataTheTextAndTheFilesAreRead(): void
    {
        $exchange = $this->withAnAttachment();

        $detail = $this->detailOf($exchange, $this->me);

        $this->assertFalse($detail->textFlagged);
        $this->assertSame(['devis.pdf'], array_column($detail->attachments, 'name'));
        $this->assertSame(0, $detail->hiddenAttachments);
    }

    public function testWithoutTheRightTheTextIsFlaggedAndTheFilesHidden(): void
    {
        $exchange = $this->withAnAttachment();

        $detail = $this->detailOf($exchange, new Prospector(new MemberId(self::newIdentity()), $this->me->accountId));

        $this->assertTrue($detail->textFlagged);
        $this->assertSame([], $detail->attachments);
        $this->assertSame(1, $detail->hiddenAttachments);
    }

    public function testWhileItsContactIsKnownNothingIsToMakeConform(): void
    {
        $detail = $this->detailOf($this->withAnAttachment(), $this->me);

        $this->assertSame([false, false, 'present'], [$detail->toMakeConform, $detail->textToMakeConform, $detail->contactPresence]);
        $this->assertNotContains(ExchangeIntent::MakeConform->name, $detail->possibleIntents);
    }

    public function testItsContactForgottenItIsToMakeConformUntilDeclaredSo(): void
    {
        $exchange = $this->withAnAttachment();
        $exchange->forgetItsContact(by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $forgotten = $this->detailOf($exchange, $this->me);
        $exchange->declareConform(by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);
        $declared = $this->detailOf($exchange, $this->me);

        $this->assertSame([true, true, true, 'forgotten'], [$forgotten->toMakeConform, $forgotten->textToMakeConform, $forgotten->attachments[0]->toMakeConform, $forgotten->contactPresence]);
        $this->assertContains(ExchangeIntent::MakeConform->name, $forgotten->possibleIntents);
        $this->assertSame([true, false, $this->me->id->value], [$declared->toMakeConform, $declared->textToMakeConform, $declared->madeConformBy]);
        $this->assertNotContains(ExchangeIntent::MakeConform->name, $declared->possibleIntents);
    }

    public function testAFullExchangeOffersNoRoomForAnotherFile(): void
    {
        $exchange = $this->noted();

        foreach (range(1, Exchange::MOST_ATTACHMENTS) as $ignored) {
            $exchange->attach($this->anAttachment(), by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        }
        $this->exchanges()->save($exchange);

        $this->assertNotContains(ExchangeIntent::AttachFile->name, $this->detailOf($exchange, $this->me)->possibleIntents);
        $this->assertContains(ExchangeIntent::AttachFile->name, $this->detailOf($this->withAnAttachment(), $this->me)->possibleIntents);
    }

    public function testItsHolderMarksAPlannedExchangeOnceItsTimeHasComeAndMovesOrCancelsItBefore(): void
    {
        $planned = $this->planned('2026-10-20 14:00 UTC');
        $handling = ['MarkExchangeDone', 'MarkExchangeMissed', 'MoveExchange', 'CancelExchange'];

        $this->assertSame(['MoveExchange', 'CancelExchange'], array_values(array_intersect($handling, $this->detailOf($planned, $this->me)->possibleIntents)));
        $this->assertSame($handling, array_values(array_intersect($handling, $this->detailOf($planned, $this->me, '2026-10-20 14:00 UTC')->possibleIntents)));
        $this->assertSame([], array_intersect($handling, $this->detailOf($planned, new Prospector(new MemberId(self::newIdentity()), $this->me->accountId, managesPersonalData: true), '2026-10-20 14:00 UTC')->possibleIntents));
    }

    public function testACancelledExchangeTellsWhoCalledItOff(): void
    {
        $planned = $this->planned('2026-10-20 14:00 UTC');
        $planned->cancel(Canceller::Contact, by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($planned);

        $detail = $this->detailOf($planned, $this->me);

        $this->assertSame(['cancelled', 'contact'], [$detail->status, $detail->canceller]);
        $this->assertNotContains('CorrectExchange', $detail->possibleIntents);
    }

    public function testACallNotedMissedBeforeTheStatusesReadsAsMissed(): void
    {
        $exchange = $this->noted();
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);
        $item = $table->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);
        unset($item['exchange']['status']);
        $item['exchange']['facts']['missed'] = true;
        $table->batch([$item]);

        $this->assertSame('missed', $this->detailOf($exchange, $this->me)->status);
        $this->assertSame(ExchangeStatus::Missed, Hydrator::stateOf($this->exchanges()->get($exchange->id()))['status']);
    }

    private function planned(string $at): Exchange
    {
        $exchange = Exchange::plannedWithAContact($this->me->accountId, $this->me->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable($at), Duration::inMinutes(null), null), new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function withAnAttachment(): Exchange
    {
        $exchange = $this->noted();
        $exchange->attach($this->anAttachment(), by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function noted(): Exchange
    {
        $exchange = Exchange::notedWithAContact($this->me->accountId, $this->me->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-10 09:00 UTC'), 'Rappeler lundi'), ExchangeStatus::Done, new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function anAttachment(): Attachment
    {
        return new Attachment(new AttachmentId(self::newIdentity()), 'devis.pdf', 2048, 'application/pdf', bin2hex(random_bytes(16)));
    }

    private function detailOf(Exchange $exchange, Prospector $reader, string $asOf = self::NOW): ExchangeDetail
    {
        return self::getContainer()->get(ExchangeDetailRetriever::class)->get($exchange->id(), $reader, new \DateTimeImmutable($asOf));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
