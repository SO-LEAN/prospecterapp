<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges as Prospecting publishes them to the calendars of their holders, read from the item the repository
 * wrote (ADR 0127): booked by the public link of its holder, an exchange says so, its booker invited (ADR 0175).
 */
class DynamoHeldExchangeRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-11-23 10:00 UTC';

    public function testAnExchangeBookedByThePublicLinkSaysSo(): void
    {
        $accountId = new AccountId(self::newIdentity());
        $exchange = Exchange::bookedByAContact(new Gesture($accountId, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::newIdentity()), []), ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable('2026-11-24 08:00 UTC'), Duration::inMinutes(15), null));
        self::getContainer()->get(ExchangeRepository::class)->save($exchange);

        $held = self::getContainer()->get(HeldExchangeRetriever::class)->ofIds($accountId, [$exchange->id()]);

        $this->assertSame([true], array_map(static fn (HeldExchange $exchange): bool => $exchange->bookedByLink, $held));
    }
}
