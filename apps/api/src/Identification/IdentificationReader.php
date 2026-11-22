<?php

declare(strict_types=1);

namespace App\Identification;

use App\Identification\Resource\CandidateResource;
use App\Identification\Resource\IdentificationResource;
use App\Identification\Resource\IdentificationsResource;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\Candidate as PersonCandidate;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\Identification as PersonIdentification;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\IdentifyPersons;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\KeptIdentifications as KeptPersonIdentifications;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\PersonClues;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate as OrganizationCandidate;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Identification as OrganizationIdentification;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\KeptIdentifications as KeptOrganizationIdentifications;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\OrganizationClues;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * Which persons and organizations of the account clues designate, composed of two contexts (ADR 0173): Prospecting
 * identifies the organizations first, then PersonalData the persons, each person among the organizations the one
 * they work for was identified as.
 *
 * @phpstan-type OrganizationCluesArray array{name?: string, siret?: string, links?: list<string>, website?: string, emails?: list<string>, city?: string}
 * @phpstan-type PersonCluesArray array{firstName?: string, lastName?: string, links?: list<string>, emails?: list<string>, phoneNumbers?: list<string>, organization?: int}
 */
final readonly class IdentificationReader
{
    public function __construct(
        private QueryHandler $identifyOrganizations,
        private QueryHandler $identifyPersons,
    ) {
    }

    /**
     * @param list<PersonCluesArray>       $persons
     * @param list<OrganizationCluesArray> $organizations
     */
    public function identify(string $reader, array $persons, array $organizations): IdentificationsResource
    {
        $identifiedOrganizations = $this->identifiedOrganizations($reader, $organizations);
        $identifiedPersons = new KeptPersonIdentifications();
        ($this->identifyPersons)(new IdentifyPersons($reader, array_map(static fn (array $person): PersonClues => self::personClues($person, $identifiedOrganizations), $persons)), $identifiedPersons);

        return new IdentificationsResource(
            array_map(self::resource(...), $identifiedPersons->identifications),
            array_map(self::resource(...), $identifiedOrganizations),
        );
    }

    /**
     * @param list<OrganizationCluesArray> $organizations
     *
     * @return list<OrganizationIdentification>
     */
    private function identifiedOrganizations(string $reader, array $organizations): array
    {
        $identified = new KeptOrganizationIdentifications();
        ($this->identifyOrganizations)(new IdentifyOrganizations($reader, array_map(self::organizationClues(...), $organizations)), $identified);

        return $identified->identifications;
    }

    /**
     * @param OrganizationCluesArray $organization
     */
    private static function organizationClues(array $organization): OrganizationClues
    {
        return new OrganizationClues(
            name: $organization['name'] ?? null,
            siret: $organization['siret'] ?? null,
            links: $organization['links'] ?? [],
            website: $organization['website'] ?? null,
            emails: $organization['emails'] ?? [],
            city: $organization['city'] ?? null,
        );
    }

    /**
     * A person names the organization they work for by its position in the organizations of the call: those it was
     * identified as; a position out of the list designates none.
     *
     * @param PersonCluesArray                 $person
     * @param list<OrganizationIdentification> $organizations
     */
    private static function personClues(array $person, array $organizations): PersonClues
    {
        $organization = $organizations[$person['organization'] ?? -1] ?? null;

        return new PersonClues(
            firstName: $person['firstName'] ?? null,
            lastName: $person['lastName'] ?? null,
            links: $person['links'] ?? [],
            emails: $person['emails'] ?? [],
            phoneNumbers: $person['phoneNumbers'] ?? [],
            organizationIds: null === $organization ? [] : array_map(static fn (OrganizationCandidate $candidate): string => $candidate->id, $organization->candidates),
        );
    }

    private static function resource(PersonIdentification|OrganizationIdentification $identification): IdentificationResource
    {
        return new IdentificationResource($identification->status, array_map(
            static fn (PersonCandidate|OrganizationCandidate $candidate): CandidateResource => new CandidateResource($candidate->id, $candidate->name),
            $identification->candidates,
        ));
    }
}
