<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\ProposedOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The organizations of an account by the domain of their email, read through the links `DynamoOrganizationRepository`
 * writes (`EMAIL_DOMAIN#<account>#<domain>`), without restoring them (ADR 0088, 0146): the first by identity.
 */
final readonly class DynamoProposedOrganizationRetriever implements ProposedOrganizationRetriever
{
    private const string FIRST = '0';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function firstWithEmailDomain(AccountId $accountId, string $domain): ?ProposedOrganization
    {
        $first = $this->prospectingTable->queryAfter(OrganizationKeys::ofEmailDomain($accountId->value, $domain), null, self::FIRST, 1)[0] ?? null;
        $item = null === $first ? null : $this->prospectingTable->item($first['SK'], OrganizationKeys::ORGANIZATION);

        return null === $item ? null : self::proposed($item['organization']);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function proposed(array $document): ProposedOrganization
    {
        $name = Documents::legalIdentityOf($document['legalIdentity'])->fullName();

        return new ProposedOrganization($document['id'], '' === $name ? $document['contactDetails']['email'] : $name);
    }
}
