<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
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
     * The exchange the account logged under this fingerprint, whether erased since or not (ADR 0166).
     */
    public function fingerprinted(AccountId $accountId, ExchangeFingerprint $fingerprint): ?Fingerprinted;

    /**
     * The fingerprints of the account's exchanges with the contact, erased or not. They are forgotten with the contact.
     */
    public function forgetFingerprintsOf(AccountId $accountId, PersonId $contact): void;

    /**
     * The exchanges kept for this organization.
     *
     * @return list<Exchange>
     */
    public function keptFor(OrganizationId $organization): array;

    /**
     * The account's exchanges with the contact.
     *
     * @return list<Exchange>
     */
    public function withContact(PersonId $contact, AccountId $accountId): array;

    /**
     * The contacts, among these, with whom the account has an exchange in the campaign (ADR 0195).
     *
     * @param list<PersonId> $contacts
     *
     * @return list<PersonId>
     */
    public function contactsWithAnExchangeOf(AccountId $accountId, CampaignId $campaign, array $contacts): array;
}
