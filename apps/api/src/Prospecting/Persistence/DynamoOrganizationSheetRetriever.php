<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Rule\OrganizationPossibilities;
use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Gesture\Origin;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads the items `ORGANIZATION#<id>` / `ORGANIZATION` that `DynamoOrganizationRepository` writes, as sheets. It does
 * not restore the organizations (ADR 0088). It finds a calling organization through the phone numbers' index (ADR
 * 0072).
 */
final readonly class DynamoOrganizationSheetRetriever implements OrganizationSheetRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(OrganizationId $id): OrganizationSheet
    {
        $item = $this->prospectingTable->item(OrganizationKeys::organization($id->value), OrganizationKeys::ORGANIZATION) ?? throw UnknownOrganization::identifiedBy($id);

        return $this->sheetOf(Typed::document($item['organization']));
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $keys = SearchIndexes::withPhoneNumber($this->prospectingTable, $accountId->value, $phoneNumber);
        $items = $this->prospectingTable->itemsInTheOrderOf($keys, OrganizationKeys::ORGANIZATION);

        return array_map(fn (array $item): OrganizationSheet => $this->sheetOf(Typed::document($item['organization'])), $items);
    }

    /**
     * @param array<string, mixed> $document
     */
    private function sheetOf(array $document): OrganizationSheet
    {
        $identity = Documents::legalIdentityOf($document['legalIdentity']);
        $contact = Documents::contactDetailsOf($document['contactDetails']);
        $address = Documents::addressOf($document['address']);
        $geoPoint = Documents::geoPointOf($document['geoPoint']);
        $id = Typed::string($document['id']);
        $possibleDuplicates = PossibleDuplicates::of($this->prospectingTable, [$document])[$id] ?? [];
        $logo = Documents::fileOf($document['logo']);
        $holdingId = Typed::stringOrNull($document['holdingId']);
        $contactIds = Typed::strings($document['contacts']);

        return new OrganizationSheet(
            id: $id,
            accountId: Typed::string($document['accountId']),
            createdBy: Typed::string($document['createdBy']),
            corporateName: $identity->corporateName,
            form: $identity->form,
            type: $identity->type,
            fullName: $identity->fullName(),
            siret: $identity->siret?->value,
            email: $contact->email?->value,
            phoneNumber: $contact->phoneNumber?->international(),
            language: $contact->language,
            links: ExternalLink::allOf(OrganizationDocument::referencesOf($document)),
            website: Typed::stringOrNull($document['website'] ?? null),
            street: $address?->street,
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            municipalityCode: Typed::stringOrNull($document['municipalityCode'] ?? null),
            observations: Typed::stringOrNull($document['observations']),
            logo: FileResponse::orNone($logo),
            holdingId: $holdingId,
            contactIds: $contactIds,
            createdAt: Documents::momentOf(Typed::string($document['createdAt'])),
            modifiedAt: Documents::momentOf(Typed::string($document['modifiedAt'])),
            possibleIntents: array_map(static fn (OrganizationIntent $intent): string => $intent->name, OrganizationPossibilities::given(hasLogo: null !== $logo, hasHolding: null !== $holdingId, hasContacts: [] !== $contactIds, hasPossibleDuplicates: [] !== $possibleDuplicates)),
            possibleDuplicates: $possibleDuplicates,
            createdByAnAI: Origin::Ai === Documents::originOf($document),
        );
    }
}
