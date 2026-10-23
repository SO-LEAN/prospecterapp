<?php

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An exchange as the API shows it: read in Prospecting, with the organization itself when it was with no contact,
 * its contact and the members who noted, corrected and made it conform named by PersonalData under the person ids Accounts gives
 * them, then presented. A controller calls it after a
 * command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ExchangeReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultExchange,
        private RefusalTranslatingHandler $consultMember,
        private RefusalTranslatingHandler $consultPersons,
        private RefusalTranslatingHandler $consultOrganization,
        private ExchangeDetailPresenter $presenter,
    ) {
    }

    public function read(string $prospector, string $id): ExchangeDetailResource
    {
        $exchange = ($this->consultExchange)(new ConsultExchange($prospector, $id));
        $personOfMember = $this->personsOfMembers(array_filter([$exchange->createdBy, $exchange->correctedBy, $exchange->madeConformBy]));
        $persons = ($this->consultPersons)(new ConsultPersons($prospector, array_values(array_unique(array_filter([$exchange->personId, ...array_values($personOfMember)])))));

        return $this->presenter->present($exchange, $this->interlocutorOf($prospector, $exchange, $persons), $persons, $personOfMember);
    }

    /**
     * The contact it was with, or the organization itself, the only one it is kept for.
     *
     * @param array<string, PersonResponse> $persons by person id
     */
    private function interlocutorOf(string $prospector, ExchangeDetailResponse $exchange, array $persons): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization(($this->consultOrganization)(new ConsultOrganization($prospector, $exchange->organizationIds[0])))
            : InterlocutorResource::ofPerson($exchange->personId, $persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }

    /**
     * @param array<string> $members
     *
     * @return array<string, string> the person id of each member, by member id
     */
    private function personsOfMembers(array $members): array
    {
        $persons = [];

        foreach (array_unique($members) as $member) {
            $persons[$member] = $this->memberOf($member)->personId;
        }

        return $persons;
    }

    private function memberOf(string $id): MemberResponse
    {
        return ($this->consultMember)(new ConsultMember($id));
    }
}
