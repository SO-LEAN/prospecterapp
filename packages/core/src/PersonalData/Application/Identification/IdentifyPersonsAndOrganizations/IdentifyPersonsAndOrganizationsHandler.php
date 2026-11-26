<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations;

use Prospecter\Core\PersonalData\Application\Identification\Candidate;
use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\PersonalData\Application\Identification\PersonClues;
use Prospecter\Core\PersonalData\Application\Identification\PersonIdentifier;
use Prospecter\Core\PersonalData\Application\Port\OrganizationIdentificationRetriever;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Prospecting identifies the organizations first (ADR 0173). Each person is then searched first among the contacts of
 * the candidates found for the organization they name. A position outside the call's organizations names none.
 */
final readonly class IdentifyPersonsAndOrganizationsHandler implements QueryHandler
{
    public function __construct(
        private OrganizationIdentificationRetriever $organizationIdentificationRetriever,
        private PersonIdentifier $personIdentifier,
        private Readers $readers,
    ) {
    }

    public function __invoke(IdentifyPersonsAndOrganizations $query, IdentifyPersonsAndOrganizationsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $organizations = $this->organizationIdentificationRetriever->identify(new MemberId($query->readerId), $query->organizations);

        $output->present(
            array_map(fn (PersonAtOrganization $person): Identification => $this->personIdentifier->identify($reader, self::cluesOf($person, $organizations)), $query->persons),
            $organizations,
        );
    }

    /**
     * @param list<Identification> $organizations
     */
    private static function cluesOf(PersonAtOrganization $person, array $organizations): PersonClues
    {
        $organization = null === $person->organization ? null : $organizations[$person->organization] ?? null;
        $clues = $person->clues;

        return new PersonClues(
            firstName: $clues->firstName,
            lastName: $clues->lastName,
            links: $clues->links,
            emails: $clues->emails,
            phoneNumbers: $clues->phoneNumbers,
            organizationIds: null === $organization ? [] : array_map(static fn (Candidate $candidate): string => $candidate->id, $organization->candidates),
        );
    }
}
