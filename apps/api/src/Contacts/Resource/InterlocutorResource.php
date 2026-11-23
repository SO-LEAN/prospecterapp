<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

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
     * Gives who an exchange is with: the person, or the organization itself.
     *
     * @param array<string, PersonCard>        $persons       the exchanges' persons, by id
     * @param array<string, OrganizationSheet> $organizations the organizations that exchanges are held with directly, with no contact, by id
     */
    public static function of(ExchangeInTimeline $exchange, array $persons, array $organizations): self
    {
        return null === $exchange->personId
            ? self::ofTheOrganization($organizations[$exchange->organizationIds[0]])
            : self::ofPerson($exchange->personId, $persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }

    /**
     * Keeps the exchanges whose interlocutor is still known. A person always is, by their id. An organization erased
     * before its exchanges is not.
     *
     * @param list<ExchangeInTimeline>         $exchanges
     * @param array<string, OrganizationSheet> $organizations the organizations that exchanges are held with directly, with no contact, by id
     *
     * @return list<ExchangeInTimeline>
     */
    public static function known(array $exchanges, array $organizations): array
    {
        return array_values(array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => null !== $exchange->personId || isset($organizations[$exchange->organizationIds[0]])));
    }

    public static function ofTheOrganization(OrganizationSheet $organization): self
    {
        return new self(self::ORGANIZATION, $organization->id, $organization->fullName);
    }

    /**
     * @param ?PersonCard $person   none for a person who is no longer known
     * @param ?string     $presence by the values of `ContactPresence`
     */
    public static function ofPerson(string $personId, ?PersonCard $person, ?string $presence): self
    {
        return new self(self::PERSON, $personId, null === $person ? null : self::fullNameOf($person), $presence);
    }

    private static function fullNameOf(PersonCard $person): ?string
    {
        $name = trim(sprintf('%s %s', $person->firstName, $person->lastName));

        return '' === $name ? null : $name;
    }
}
