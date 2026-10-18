<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization as a prospector of its account sees it, with what they may do with it now.
 */
final readonly class OrganizationResponse
{
    /**
     * @param list<string> $contactIds      the persons prospected in it, to read in PersonalData
     * @param list<string> $possibleIntents names of the intentions the prospector may carry out now: Relocate, RemoveLogo...
     */
    private function __construct(
        public string $id,
        public string $accountId,
        public string $createdBy,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public string $fullName,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?float $longitude,
        public ?float $latitude,
        public ?string $observations,
        public ?FileResponse $logo,
        public ?string $holdingId,
        public array $contactIds,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }

    public static function of(Organization $organization, Prospector $viewer): self
    {
        $identity = $organization->legalIdentity();
        $contact = $organization->contactDetails();
        $address = $organization->address();
        $geoPoint = $organization->geoPoint();

        return new self(
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
            possibleIntents: array_map(static fn (OrganizationIntent $intent): string => $intent->name, $organization->possibleIntentsOf($viewer)),
            createdAt: $organization->createdAt(),
            modifiedAt: $organization->modifiedAt(),
        );
    }
}
