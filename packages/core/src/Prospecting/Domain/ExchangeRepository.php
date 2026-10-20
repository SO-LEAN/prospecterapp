<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

interface ExchangeRepository
{
    /**
     * @throws UnknownExchange
     */
    public function get(ExchangeId $id): Exchange;

    public function save(Exchange $exchange): void;

    public function remove(Exchange $exchange): void;

    /**
     * The exchanges kept for this organization.
     *
     * @return list<Exchange>
     */
    public function keptFor(OrganizationId $organization): array;

    /**
     * The exchanges of the account with the contact, the most recent moment first, after the cursor (the
     * cursor of a slice is where the next one starts, ADR 0074).
     *
     * @return Slice<Exchange>
     */
    public function timelineOf(PersonId $contact, AccountId $accountId, ?string $after, int $max): Slice;

    /**
     * The exchanges kept for this organization, with it or with its contacts, the most recent moment first,
     * after the cursor.
     *
     * @return Slice<Exchange>
     */
    public function timelineOfOrganization(OrganizationId $organization, ?string $after, int $max): Slice;
}
