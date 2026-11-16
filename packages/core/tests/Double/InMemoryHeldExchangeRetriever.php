<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads their items, and the agenda of a holder
 * as it reads its links: those still planned, by their moment then their id.
 */
final readonly class InMemoryHeldExchangeRetriever implements HeldExchangeRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    /**
     * @param list<ExchangeId> $ids
     */
    public function ofIds(AccountId $accountId, array $ids): array
    {
        $kept = array_filter(
            array_map(fn (ExchangeId $id): ?Exchange => $this->database->exchange($id->value), $ids),
            static fn (?Exchange $exchange): bool => null !== $exchange && $exchange->accountId()->equals($accountId),
        );

        return array_values(array_map(self::heldExchangeOf(...), $kept));
    }

    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array
    {
        $planned = array_values(array_filter($this->database->exchanges(), static function (Exchange $exchange) use ($accountId, $holder, $from): bool {
            $state = StateOf::the($exchange);

            return ExchangeStatus::Planned === $state['status'] && $exchange->accountId()->equals($accountId) && $state['createdBy']->equals($holder) && $state['facts']->occurredAt >= $from;
        }));
        usort($planned, static fn (Exchange $a, Exchange $b): int => [StateOf::the($a)['facts']->occurredAt, (string) $a->id()] <=> [StateOf::the($b)['facts']->occurredAt, (string) $b->id()]);

        return array_map(self::heldExchangeOf(...), $planned);
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
            $facts->linkRequested,
            $state['contact']?->value,
            array_map(strval(...), $state['keptFor']),
        );
    }
}
