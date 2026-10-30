<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

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
     * The exchanges of the account with the contact.
     *
     * @return list<Exchange>
     */
    public function withContact(PersonId $contact, AccountId $accountId): array;
}
