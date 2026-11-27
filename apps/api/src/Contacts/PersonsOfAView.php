<?php

declare(strict_types=1);

namespace App\Contacts;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * A part of the compositions that show the persons in another context's view (ADR 0217): the view's contacts, and the
 * members who hold or wrote what it shows. Accounts gives each member's person. PersonalData reads them all, as the
 * reader may read them, in one call. The parts are joined by the persons' ids.
 */
final readonly class PersonsOfAView
{
    public function __construct(private ConsultMembersService $consultMembersService, private QueryHandler $consultPersons)
    {
    }

    /**
     * @param list<string> $contactIds the persons the view shows as contacts, each one any number of times
     * @param list<string> $memberIds  the members the view shows, each one any number of times
     */
    public function shownTo(string $reader, array $contactIds, array $memberIds = []): PersonsShown
    {
        $personOfMember = $this->personsOfMembers($memberIds);
        $cards = $this->cards($reader, [...$contactIds, ...array_values($personOfMember)]);

        return new PersonsShown(
            array_intersect_key($cards, array_flip($contactIds)),
            array_filter(array_map(static fn (string $personId): ?PersonCard => $cards[$personId] ?? null, $personOfMember)),
        );
    }

    /**
     * @param list<string> $memberIds
     *
     * @return array<string, string> the person id of each member Accounts knows, by member id
     */
    private function personsOfMembers(array $memberIds): array
    {
        if ([] === $memberIds) {
            return [];
        }

        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique($memberIds))), $kept);

        return array_map(static fn (MemberResponse $member): string => $member->personId, $kept->members);
    }

    /**
     * @param list<string> $personIds
     *
     * @return array<string, PersonCard> by person id, the persons PersonalData gives back
     */
    private function cards(string $reader, array $personIds): array
    {
        if ([] === $personIds) {
            return [];
        }

        $kept = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, array_values(array_unique($personIds))), $kept);

        return $kept->persons;
    }
}
