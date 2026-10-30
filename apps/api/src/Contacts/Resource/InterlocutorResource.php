<?php

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Who communicated in an exchange of the timeline of an organization: one of its contacts, still, removed from it
 * or forgotten, or the organization itself.
 */
final readonly class InterlocutorResource
{
    public const string ORGANIZATION = 'organization';
    public const string PERSON = 'person';
    public const string FORGOTTEN = 'forgotten';
    private const array PRESENCES = ['present', 'removed', self::FORGOTTEN];

    public function __construct(
        #[OA\Property(enum: [self::ORGANIZATION, self::PERSON])]
        public string $kind,
        public string $id,
        #[OA\Property(description: 'Its name; none for a person no longer known')]
        public ?string $name,
        #[OA\Property(enum: self::PRESENCES, description: 'A person still a contact of the organization, removed from it, or forgotten; none for the organization itself')]
        public ?string $presence = null,
    ) {
    }

    public static function ofTheOrganization(OrganizationSheet $organization): self
    {
        return new self(self::ORGANIZATION, $organization->id, $organization->fullName);
    }

    /**
     * @param ?PersonResponse $person   none for a person no longer known
     * @param ?string         $presence by the values of `ContactPresence`
     */
    public static function ofPerson(string $personId, ?PersonResponse $person, ?string $presence): self
    {
        return new self(self::PERSON, $personId, null === $person ? null : self::fullNameOf($person), $presence);
    }

    private static function fullNameOf(PersonResponse $person): ?string
    {
        $name = trim(sprintf('%s %s', $person->firstName, $person->lastName));

        return '' === $name ? null : $name;
    }
}
