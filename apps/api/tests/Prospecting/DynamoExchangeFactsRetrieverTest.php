<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
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

        $this->assertEquals(new ExchangeFactsResponse($exchange->id()->value, 2, true, 'done', new \DateTimeImmutable('2026-10-09 08:00 UTC')), $this->retriever()->of($exchange->id()));
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
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);
        $item = $table->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);
        unset($item['GSI2PK'], $item['GSI2SK']);
        $table->batch([$item]);

        self::getContainer()->get(DynamoExchangeRepository::class)->writeTheKeysAgain();

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
        return Exchange::notedWithAContact($this->prospector->accountId, $this->prospector->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt)), ExchangeStatus::Done, new \DateTimeImmutable(self::NOW));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }

    private function retriever(): ExchangeFactsRetriever
    {
        return self::getContainer()->get(ExchangeFactsRetriever::class);
    }
}
