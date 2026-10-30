<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
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

    private function withAnAttachment(): Exchange
    {
        $exchange = $this->noted();
        $exchange->attach($this->anAttachment(), by: $this->me->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function noted(): Exchange
    {
        $exchange = Exchange::notedWithAContact($this->me->accountId, $this->me->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-10 09:00 UTC'), 'Rappeler lundi'), new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function anAttachment(): Attachment
    {
        return new Attachment(new AttachmentId(self::newIdentity()), 'devis.pdf', 2048, 'application/pdf', bin2hex(random_bytes(16)));
    }

    private function detailOf(Exchange $exchange, Prospector $reader): ExchangeDetail
    {
        return self::getContainer()->get(ExchangeDetailRetriever::class)->get($exchange->id(), $reader);
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
