<?php

declare(strict_types=1);

namespace App\Campaigning;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * A part of the campaigns' composition (ADR 0217): their authors, read in one batch from each context. Accounts gives
 * each author's person, and PersonalData names them for the reader. The parts are joined by the persons' ids.
 */
final readonly class CampaignAuthors
{
    public function __construct(private ConsultMembersService $consultMembersService, private QueryHandler $consultPersons)
    {
    }

    /**
     * @param list<string> $authorIds the authors' member ids
     *
     * @return array<string, PersonCard> each author's person that PersonalData reads, by member id
     */
    public function of(string $reader, array $authorIds): array
    {
        $members = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique($authorIds))), $members);
        $personOfMember = array_map(static fn (MemberResponse $member): string => $member->personId, $members->members);
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, array_values($personOfMember)), $persons);

        return array_filter(array_map(static fn (string $personId): ?PersonCard => $persons->persons[$personId] ?? null, $personOfMember));
    }
}
