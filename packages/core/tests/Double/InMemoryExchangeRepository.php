<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Like the DynamoDB repository: a timeline, of a contact or of an organization, ordered by the moment of the exchanges then their identity, the
 * most recent first, its cursor the moment and the identity of the last exchange of the slice.
 */
final readonly class InMemoryExchangeRepository implements ExchangeRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function save(Exchange $exchange): void
    {
        $this->database->storeExchange($exchange);
    }

    public function remove(Exchange $exchange): void
    {
        $this->database->forgetExchange((string) $exchange->id());
    }

    public function keptFor(OrganizationId $organization): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => array_any($exchange->keptFor(), $organization->equals(...))));
    }

    public function timelineOf(PersonId $contact, AccountId $accountId, ?string $after, int $max): Slice
    {
        return self::sliceOf(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => true === $exchange->contact()?->equals($contact) && $exchange->accountId()->equals($accountId)), $after, $max);
    }

    public function timelineOfOrganization(OrganizationId $organization, ?string $after, int $max): Slice
    {
        return self::sliceOf($this->keptFor($organization), $after, $max);
    }

    /**
     * @param array<Exchange> $exchanges
     *
     * @return Slice<Exchange>
     */
    private static function sliceOf(array $exchanges, ?string $after, int $max): Slice
    {
        $timeline = array_values(array_filter($exchanges, static fn (Exchange $exchange): bool => null === $after || strcmp(self::positionOf($exchange), $after) < 0));
        usort($timeline, static fn (Exchange $a, Exchange $b): int => strcmp(self::positionOf($b), self::positionOf($a)));
        $slice = array_slice($timeline, 0, $max);

        return new Slice($slice, count($timeline) > $max ? self::positionOf(array_last($slice)) : null);
    }

    private static function positionOf(Exchange $exchange): string
    {
        return $exchange->facts()->occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z').'#'.$exchange->id();
    }
}
