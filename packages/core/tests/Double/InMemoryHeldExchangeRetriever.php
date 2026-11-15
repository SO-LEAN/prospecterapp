<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads their items.
 */
final readonly class InMemoryHeldExchangeRetriever implements HeldExchangeRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function ofIds(AccountId $accountId, array $ids): array
    {
        $kept = array_filter(
            array_map(fn (ExchangeId $id): ?Exchange => $this->database->exchange($id->value), $ids),
            static fn (?Exchange $exchange): bool => null !== $exchange && $exchange->accountId()->equals($accountId),
        );

        return array_values(array_map(self::heldExchangeOf(...), $kept));
    }

    private static function heldExchangeOf(Exchange $exchange): HeldExchange
    {
        $state = StateOf::the($exchange);
        $facts = $state['facts'];

        return new HeldExchange(
            (string) $state['id'],
            (string) $state['createdBy'],
            $state['status']->value,
            $facts->channel->value,
            $facts->occurredAt,
            $facts->duration?->minutes,
            $facts->place,
            $facts->link?->url,
            $state['contact']?->value,
            (string) $state['keptFor'][0],
        );
    }
}
