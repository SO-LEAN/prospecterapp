<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization as a plain document of its DynamoDB item, and back (ADR 0048): its value objects as they
 * are, its recorded events never.
 */
final class OrganizationDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Organization $organization): array
    {
        $state = Hydrator::stateOf($organization);

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'holdingId' => null === $state['holdingId'] ? null : (string) $state['holdingId'],
            'legalIdentity' => Documents::legalIdentity($state['legalIdentity']),
            'contactDetails' => Documents::contactDetails($state['contactDetails']),
            'address' => Documents::address($state['address']),
            'geoPoint' => Documents::geoPoint($state['geoPoint']),
            'observations' => $state['observations'],
            'logo' => Documents::file($state['logo']),
            'contacts' => array_map(static fn (PersonId $contact): string => (string) $contact, $state['contacts']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function organization(array $document): Organization
    {
        return Hydrator::restore(Organization::class, [
            'id' => new OrganizationId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'holdingId' => null === $document['holdingId'] ? null : new OrganizationId($document['holdingId']),
            'legalIdentity' => Documents::legalIdentityOf($document['legalIdentity']),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'address' => Documents::addressOf($document['address']),
            'geoPoint' => Documents::geoPointOf($document['geoPoint']),
            'observations' => $document['observations'],
            'logo' => Documents::fileOf($document['logo']),
            'contacts' => array_map(static fn (string $contact): PersonId => new PersonId($contact), $document['contacts']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }
}
