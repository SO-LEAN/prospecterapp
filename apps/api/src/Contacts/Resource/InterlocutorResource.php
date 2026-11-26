<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Agenda\Interlocutors;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PersonDescription;

/**
 * Who took part in an exchange on an organization's timeline: the organization itself, or one of its contacts. The
 * contact may still be one, or may be removed from it or forgotten.
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
        #[OA\Property(description: 'Its name. A person who is no longer known has none')]
        public ?string $name,
        #[OA\Property(enum: self::PRESENCES, description: 'Whether the person is still a contact of the organization, removed from it, or forgotten. The organization itself has none')]
        public ?string $presence = null,
    ) {
    }

    /**
     * Gives who an agenda's exchange is with: the person, or the organization itself.
     */
    public static function of(ExchangeInTimeline $exchange, Interlocutors $interlocutors): self
    {
        return null === $exchange->personId
            ? self::ofTheOrganization($interlocutors->organizationsMetAlone[$exchange->organizationIds[0]])
            : self::ofPerson($exchange->personId, $interlocutors->persons->contacts[$exchange->personId] ?? null, $exchange->contactPresence);
    }

    public static function ofTheOrganization(OrganizationSheet $organization): self
    {
        return new self(self::ORGANIZATION, $organization->id, $organization->fullName);
    }

    public static function ofAnOrganizationNoLongerKnown(string $organizationId): self
    {
        return new self(self::ORGANIZATION, $organizationId, null);
    }

    /**
     * @param ?PersonDescription $person   none for a person who is no longer known
     * @param ?string            $presence by the values of `ContactPresence`
     */
    public static function ofPerson(string $personId, ?PersonDescription $person, ?string $presence): self
    {
        return new self(self::PERSON, $personId, null === $person ? null : self::fullNameOf($person), $presence);
    }

    private static function fullNameOf(PersonDescription $person): ?string
    {
        $name = trim(sprintf('%s %s', $person->firstName, $person->lastName));

        return '' === $name ? null : $name;
    }
}
