<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization as a plain document of its DynamoDB item, and back (ADR 0048): its value objects as they
 * are, its recorded events never; the code of its municipality is absent from the documents written before it was
 * kept (ADR 0142), its website from those written before it was, its links from those written before them (their LinkedIn page then, ADR 0160), the organizations it
 * may be a duplicate of, and those it is no duplicate of, from those written before it was suspected of any.
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
            'references' => Documents::references($state['references']),
            'website' => $state['website']?->value,
            'address' => Documents::address($state['address']),
            'geoPoint' => Documents::geoPoint($state['geoPoint']),
            'municipalityCode' => $state['municipalityCode'],
            'observations' => $state['observations'],
            'logo' => Documents::file($state['logo']),
            'contacts' => array_map(static fn (PersonId $contact): string => (string) $contact, $state['contacts']),
            'possibleDuplicates' => array_map(static fn (OrganizationId $duplicate): string => (string) $duplicate, $state['possibleDuplicates']),
            'notDuplicates' => array_map(static fn (OrganizationId $checked): string => (string) $checked, $state['notDuplicates']),
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
            'references' => self::referencesOf($document),
            'website' => Website::orNone($document['website'] ?? null),
            'address' => Documents::addressOf($document['address']),
            'geoPoint' => Documents::geoPointOf($document['geoPoint']),
            'municipalityCode' => $document['municipalityCode'] ?? null,
            'observations' => $document['observations'],
            'logo' => Documents::fileOf($document['logo']),
            'contacts' => array_map(static fn (string $contact): PersonId => new PersonId($contact), $document['contacts']),
            'possibleDuplicates' => array_map(static fn (string $duplicate): OrganizationId => new OrganizationId($duplicate), $document['possibleDuplicates'] ?? []),
            'notDuplicates' => array_map(static fn (string $checked): OrganizationId => new OrganizationId($checked), $document['notDuplicates'] ?? []),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * Its links as they were noted; its LinkedIn page for a document written before the links were.
     *
     * @param array<string, mixed> $document
     */
    public static function referencesOf(array $document): ExternalReferences
    {
        return isset($document['references']) ? Documents::referencesOf($document['references']) : Documents::referencesBeforeTheLinks(array_filter([$document['linkedInPage'] ?? null]), Designated::Organization);
    }

    /**
     * The document with its links recognized again by their addresses: what `app:keys:rebuild` writes once a source
     * is known.
     *
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public static function recognizedAgain(array $document): array
    {
        return ['references' => Documents::referencesRecognizedAgain(Documents::references(self::referencesOf($document)), Designated::Organization)] + $document;
    }
}
