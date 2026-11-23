<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\ProposedOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads an account's organizations by their email's domain, through the links that `DynamoOrganizationRepository`
 * writes (`EMAIL_DOMAIN#<account>#<domain>`). It does not restore them (ADR 0088, 0146). It takes the first one by
 * identity.
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
        $item = null === $first ? null : $this->prospectingTable->item(Typed::string($first['SK']), OrganizationKeys::ORGANIZATION);

        return null === $item ? null : self::proposed(Typed::document($item['organization']));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function proposed(array $document): ProposedOrganization
    {
        $name = Documents::legalIdentityOf($document['legalIdentity'])->fullName();

        return new ProposedOrganization(Typed::string($document['id']), '' === $name ? Typed::string(Typed::document($document['contactDetails'])['email']) : $name);
    }
}
