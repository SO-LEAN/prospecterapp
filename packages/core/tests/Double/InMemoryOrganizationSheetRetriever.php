<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Rule\OrganizationPossibilities;
use Prospecter\Core\SharedKernel\Application\Response\ExternalLink;
use Prospecter\Core\SharedKernel\Application\Response\FileResponse;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Gesture\Origin;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Location\GeoPoint;

/**
 * Reads the organizations that the in-memory repository saved, as the adapter reads the table's items.
 */
final readonly class InMemoryOrganizationSheetRetriever implements OrganizationSheetRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(OrganizationId $id): OrganizationSheet
    {
        $organization = $this->database->organization((string) $id) ?? throw UnknownOrganization::identifiedBy($id);

        return $this->sheetOf($organization);
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $calling = array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId) && $organization->contactDetails()->phoneNumber?->value === $phoneNumber->value);

        return array_values(array_map($this->sheetOf(...), $calling));
    }

    private function sheetOf(Organization $organization): OrganizationSheet
    {
        $state = StateOf::the($organization);
        $identity = $state->object('legalIdentity', LegalIdentity::class);
        $contact = $organization->contactDetails();
        $address = $state->objectOrNull('address', Address::class);
        $geoPoint = $state->objectOrNull('geoPoint', GeoPoint::class);
        $possibleDuplicates = PossibleDuplicates::of($organization, $this->database);

        return new OrganizationSheet(
            id: $organization->id()->value,
            accountId: $organization->accountId()->value,
            createdBy: $state->object('createdBy', MemberId::class)->value,
            corporateName: $identity->corporateName,
            form: $identity->form,
            type: $identity->type,
            fullName: $identity->fullName(),
            siret: $identity->siret?->value,
            email: $contact->email?->value,
            phoneNumber: $contact->phoneNumber?->international(),
            language: $contact->language,
            links: ExternalLink::allOf($state->object('references', ExternalReferences::class)),
            website: $state->objectOrNull('website', Website::class)?->value,
            street: $address?->street,
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            municipalityCode: $state->stringOrNull('municipalityCode'),
            observations: $state->stringOrNull('observations'),
            logo: FileResponse::orNone($state->objectOrNull('logo', File::class)),
            holdingId: $state->objectOrNull('holdingId', OrganizationId::class)?->value,
            contactIds: array_map(static fn (ContactId $contact): string => $contact->value, $state->listOf('contacts', ContactId::class)),
            createdAt: $state->object('createdAt', \DateTimeImmutable::class),
            modifiedAt: $state->object('modifiedAt', \DateTimeImmutable::class),
            possibleIntents: array_map(static fn (OrganizationIntent $intent): string => $intent->name, OrganizationPossibilities::given(hasLogo: null !== $state->objectOrNull('logo', File::class), hasHolding: null !== $state->objectOrNull('holdingId', OrganizationId::class), hasContacts: [] !== $state->listOf('contacts', ContactId::class), hasPossibleDuplicates: [] !== $possibleDuplicates)),
            possibleDuplicates: $possibleDuplicates,
            createdByAnAI: Origin::Ai === $state->object('origin', Origin::class),
        );
    }
}
