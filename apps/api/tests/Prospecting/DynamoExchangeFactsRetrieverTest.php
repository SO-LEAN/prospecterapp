<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\ExchangeKeys;
use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\RecipientCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The facts of the exchanges, read from the items `DynamoExchangeRepository` writes: the revision it increments
 * at each save, the moment of the exchange, the exchanges of an account by slice, listed in `GSI2`.
 */
class DynamoExchangeFactsRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';
    private const string CONTACT = '0190a1c0-0000-7000-8000-00000000c0c0';

    private Prospector $prospector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), true);
    }

    public function testACorrectionIsANewRevisionAtTheNewMoment(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $this->exchanges()->save($exchange);

        $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-09 08:00 UTC')), ExchangeStatus::Done, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $this->assertEquals(new ExchangeFactsResponse($exchange->id()->value, 2, true, 'done', new \DateTimeImmutable('2026-10-09 08:00 UTC'), self::CONTACT), $this->retriever()->of($exchange->id()));
    }

    public function testAnExchangeNotedInACampaignNamesItsCampaignAndItsContact(): void
    {
        $campaign = new CampaignId(self::newIdentity());
        $recipient = new RecipientInCampaign($campaign, new PersonId(self::CONTACT), Channel::Call, running: true, recipient: true, pending: true);
        $noting = Noting::of($recipient->exchangeFacts(new \DateTimeImmutable('2026-10-10 09:00 UTC'), null), ExchangeStatus::Missed);
        $exchange = Exchange::noted(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new RecipientCorrespondent($recipient, [new OrganizationId(self::newIdentity())]), $noting);

        $this->exchanges()->save($exchange);

        $this->assertEquals(new ExchangeFactsResponse($exchange->id()->value, 1, true, 'missed', new \DateTimeImmutable('2026-10-10 09:00 UTC'), self::CONTACT, $campaign->value), $this->retriever()->of($exchange->id()));
    }

    public function testARemovedExchangeIsAbsent(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $this->exchanges()->save($exchange);

        $this->exchanges()->remove($exchange);

        $this->assertEquals(ExchangeFactsResponse::absent($exchange->id()->value), $this->retriever()->of($exchange->id()));
    }

    public function testTheFactsOfAnAccountComeBySliceNewestFirst(): void
    {
        $exchanges = [$this->noted('2026-10-10 09:00 UTC'), $this->noted('2026-10-11 09:00 UTC'), $this->noted('2026-10-12 09:00 UTC')];
        array_walk($exchanges, $this->exchanges()->save(...));

        $first = $this->retriever()->sliceOf($this->prospector->accountId, null, 2);
        $next = $this->retriever()->sliceOf($this->prospector->accountId, $first->next, 2);

        $this->assertSame([$exchanges[2]->id()->value, $exchanges[1]->id()->value], self::idsOf($first->content));
        $this->assertSame([$exchanges[0]->id()->value], self::idsOf($next->content));
        $this->assertNull($next->next);
    }

    public function testTheExchangesWrittenBeforeTheirListingAreListedOnceTheirKeysAreWrittenAgain(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $this->exchanges()->save($exchange);
        $table = self::tableOf('prospecting');
        $item = self::storedItem($table, ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);
        unset($item['GSI2PK'], $item['GSI2SK']);
        $table->batch([$item]);

        self::service(DynamoExchangeRepository::class)->writeTheKeysAgain();

        $this->assertSame([$exchange->id()->value], self::idsOf($this->retriever()->sliceOf($this->prospector->accountId, null, 10)->content));
        $this->assertSame(1, $this->retriever()->of($exchange->id())->revision);
    }

    /**
     * @param list<ExchangeFactsResponse> $facts
     *
     * @return list<string>
     */
    private static function idsOf(array $facts): array
    {
        return array_map(static fn (ExchangeFactsResponse $exchange): string => $exchange->exchangeId, $facts);
    }

    private function noted(string $occurredAt): Exchange
    {
        return Exchange::noted(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::CONTACT), [new OrganizationId(self::newIdentity())]), Noting::of(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt)), ExchangeStatus::Done));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::service(ExchangeRepository::class);
    }

    private function retriever(): ExchangeFactsRetriever
    {
        return self::service(ExchangeFactsRetriever::class);
    }
}
