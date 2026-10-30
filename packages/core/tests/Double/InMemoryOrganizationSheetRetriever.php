<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Policy\OrganizationPossibilities;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the organizations the in-memory repository saved, as the adapter reads the items of the table.
 */
final readonly class InMemoryOrganizationSheetRetriever implements OrganizationSheetRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(OrganizationId $id): OrganizationSheet
    {
        $organization = $this->database->organization((string) $id) ?? throw UnknownOrganization::identifiedBy($id);

        return self::sheetOf($organization);
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $calling = array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId) && $organization->contactDetails()->phoneNumber?->value === $phoneNumber->value);

        return array_values(array_map(self::sheetOf(...), $calling));
    }

    private static function sheetOf(Organization $organization): OrganizationSheet
    {
        $identity = $organization->legalIdentity();
        $contact = $organization->contactDetails();
        $address = $organization->address();
        $geoPoint = $organization->geoPoint();

        return new OrganizationSheet(
            id: $organization->id()->value,
            accountId: $organization->accountId()->value,
            createdBy: $organization->createdBy()->value,
            corporateName: $identity->corporateName,
            form: $identity->form,
            type: $identity->type,
            fullName: $organization->fullName(),
            email: $contact->email?->value,
            phoneNumber: $contact->phoneNumber?->international(),
            language: $contact->language,
            street: $address?->street,
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            observations: $organization->observations(),
            logo: FileResponse::orNone($organization->logo()),
            holdingId: $organization->holdingId()?->value,
            contactIds: array_map(static fn (PersonId $contact): string => $contact->value, $organization->contacts()),
            createdAt: $organization->createdAt(),
            modifiedAt: $organization->modifiedAt(),
            possibleIntents: array_map(static fn (OrganizationIntent $intent): string => $intent->name, OrganizationPossibilities::given(hasLogo: null !== $organization->logo(), hasHolding: null !== $organization->holdingId(), hasContacts: [] !== $organization->contacts())),
        );
    }
}
