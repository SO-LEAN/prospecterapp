<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
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
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Turns an organization into a plain document for its DynamoDB item, and back (ADR 0048). Its value objects are
 * stored as they are. Its recorded events are never stored. Older documents lack some fields:
 * - a document written before Prospecter kept municipalities has no municipality code (ADR 0142);
 * - a document written before Prospecter kept websites has no website;
 * - a document written before Prospecter kept links has none. It has a LinkedIn page instead (ADR 0160);
 * - a document written before Prospecter suspected duplicates has neither the organizations it may duplicate nor
 *   those it does not duplicate;
 * - a document written before Prospecter kept the origin has none, and reads as created by hand.
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
            'contacts' => array_map(static fn (ContactId $contact): string => $contact->value, Typed::objects($state['contacts'], ContactId::class)),
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
            'contacts' => array_map(static fn (string $contact): ContactId => new ContactId($contact), Typed::strings($document['contacts'])),
            'possibleDuplicates' => array_map(static fn (string $duplicate): OrganizationId => new OrganizationId($duplicate), Typed::strings($document['possibleDuplicates'] ?? [])),
            'notDuplicates' => array_map(static fn (string $checked): OrganizationId => new OrganizationId($checked), Typed::strings($document['notDuplicates'] ?? [])),
            'origin' => Documents::originOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * Its links as they were recorded. A document written before Prospecter kept links gives its LinkedIn page
     * instead.
     *
     * @param array<string, mixed> $document
     */
    public static function referencesOf(array $document): ExternalReferences
    {
        return isset($document['references']) ? Documents::referencesOf($document['references']) : Documents::referencesBeforeTheLinks(array_filter([Typed::stringOrNull($document['linkedInPage'] ?? null)]), Designated::Organization);
    }

    /**
     * Returns the document with its links recognized again from their addresses. `app:keys:rebuild` writes it once
     * Prospecter knows a new source.
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
