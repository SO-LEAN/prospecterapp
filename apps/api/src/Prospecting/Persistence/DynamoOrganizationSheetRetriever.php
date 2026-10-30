<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Policy\OrganizationPossibilities;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the items `ORGANIZATION#<id>` / `ORGANIZATION` that `DynamoOrganizationRepository` writes as sheets,
 * without restoring the organizations (ADR 0088); a calling organization by the index of the phone numbers
 * (ADR 0072).
 */
final readonly class DynamoOrganizationSheetRetriever implements OrganizationSheetRetriever
{
    private const string ORGANIZATION = 'ORGANIZATION';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(OrganizationId $id): OrganizationSheet
    {
        $item = $this->prospectingTable->item(self::keyOf($id->value), self::ORGANIZATION) ?? throw UnknownOrganization::identifiedBy($id);

        return self::sheetOf($item['organization']);
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $keys = SearchIndexes::withPhoneNumber($this->prospectingTable, $accountId->value, $phoneNumber);
        $sheets = [];

        foreach ($this->prospectingTable->items(array_map(static fn (string $key): array => [$key, self::ORGANIZATION], $keys)) as $item) {
            $sheets[$item['PK']] = self::sheetOf($item['organization']);
        }

        return array_values(array_filter(array_map(static fn (string $key): ?OrganizationSheet => $sheets[$key] ?? null, $keys)));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function sheetOf(array $document): OrganizationSheet
    {
        $identity = Documents::legalIdentityOf($document['legalIdentity']);
        $contact = Documents::contactDetailsOf($document['contactDetails']);
        $address = Documents::addressOf($document['address']);
        $geoPoint = Documents::geoPointOf($document['geoPoint']);

        return new OrganizationSheet(
            id: $document['id'],
            accountId: $document['accountId'],
            createdBy: $document['createdBy'],
            corporateName: $identity->corporateName,
            form: $identity->form,
            type: $identity->type,
            fullName: $identity->fullName(),
            email: $contact->email?->value,
            phoneNumber: $contact->phoneNumber?->international(),
            language: $contact->language,
            street: $address?->street,
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            observations: $document['observations'],
            logo: FileResponse::orNone(Documents::fileOf($document['logo'])),
            holdingId: $document['holdingId'],
            contactIds: $document['contacts'],
            createdAt: Documents::momentOf($document['createdAt']),
            modifiedAt: Documents::momentOf($document['modifiedAt']),
            possibleIntents: array_map(static fn (OrganizationIntent $intent): string => $intent->name, OrganizationPossibilities::given(hasLogo: null !== $document['logo'], hasHolding: null !== $document['holdingId'], hasContacts: [] !== $document['contacts'])),
        );
    }

    private static function keyOf(string $id): string
    {
        return self::ORGANIZATION.'#'.$id;
    }
}
