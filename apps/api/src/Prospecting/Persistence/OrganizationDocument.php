<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization as a plain document of its DynamoDB item, and back (ADR 0048): its value objects as they
 * are, its recorded events never; the code of its municipality is absent from the documents written before it was
 * kept (ADR 0142), its website from those written before it was, its links from those written before them (their LinkedIn page then, ADR 0160), the organizations it
 * may be a duplicate of, and those it is no duplicate of, from those written before it was suspected of any; its origin
 * from those written before it was kept, by hand.
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
            'id' => Typed::object($state['id'], OrganizationId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'createdBy' => Typed::object($state['createdBy'], MemberId::class)->value,
            'holdingId' => Typed::objectOrNull($state['holdingId'], OrganizationId::class)?->value,
            'legalIdentity' => Documents::legalIdentity(Typed::object($state['legalIdentity'], LegalIdentity::class)),
            'contactDetails' => Documents::contactDetails(Typed::object($state['contactDetails'], ContactDetails::class)),
            'references' => Documents::references(Typed::object($state['references'], ExternalReferences::class)),
            'website' => Typed::objectOrNull($state['website'], Website::class)?->value,
            'address' => Documents::address(Typed::objectOrNull($state['address'], Address::class)),
            'geoPoint' => Documents::geoPoint(Typed::objectOrNull($state['geoPoint'], GeoPoint::class)),
            'municipalityCode' => Typed::stringOrNull($state['municipalityCode']),
            'observations' => Typed::stringOrNull($state['observations']),
            'logo' => Documents::file(Typed::objectOrNull($state['logo'], File::class)),
            'contacts' => array_map(static fn (PersonId $contact): string => $contact->value, Typed::objects($state['contacts'], PersonId::class)),
            'possibleDuplicates' => array_map(static fn (OrganizationId $duplicate): string => $duplicate->value, Typed::objects($state['possibleDuplicates'], OrganizationId::class)),
            'notDuplicates' => array_map(static fn (OrganizationId $checked): string => $checked->value, Typed::objects($state['notDuplicates'], OrganizationId::class)),
            'origin' => Typed::object($state['origin'], Origin::class)->value,
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the organization from its item's untyped document.
     */
    public static function organization(mixed $document): Organization
    {
        $document = Typed::document($document);
        $holdingId = Typed::stringOrNull($document['holdingId']);

        return Hydrator::restore(Organization::class, [
            'id' => new OrganizationId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'createdBy' => new MemberId(Typed::string($document['createdBy'])),
            'holdingId' => null === $holdingId ? null : new OrganizationId($holdingId),
            'legalIdentity' => Documents::legalIdentityOf($document['legalIdentity']),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'references' => self::referencesOf($document),
            'website' => Website::orNone(Typed::stringOrNull($document['website'] ?? null)),
            'address' => Documents::addressOf($document['address']),
            'geoPoint' => Documents::geoPointOf($document['geoPoint']),
            'municipalityCode' => Typed::stringOrNull($document['municipalityCode'] ?? null),
            'observations' => Typed::stringOrNull($document['observations']),
            'logo' => Documents::fileOf($document['logo']),
            'contacts' => array_map(static fn (string $contact): PersonId => new PersonId($contact), Typed::strings($document['contacts'])),
            'possibleDuplicates' => array_map(static fn (string $duplicate): OrganizationId => new OrganizationId($duplicate), Typed::strings($document['possibleDuplicates'] ?? [])),
            'notDuplicates' => array_map(static fn (string $checked): OrganizationId => new OrganizationId($checked), Typed::strings($document['notDuplicates'] ?? [])),
            'origin' => Documents::originOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * Its links as they were noted; its LinkedIn page for a document written before the links were.
     *
     * @param array<string, mixed> $document
     */
    public static function referencesOf(array $document): ExternalReferences
    {
        return isset($document['references']) ? Documents::referencesOf($document['references']) : Documents::referencesBeforeTheLinks(array_filter([Typed::stringOrNull($document['linkedInPage'] ?? null)]), Designated::Organization);
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
