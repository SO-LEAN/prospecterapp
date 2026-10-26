<?php

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\KeptMember;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\KeptExchange;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An exchange as the API shows it: read in Prospecting, with the organization itself when it was with no contact,
 * its contact and the members who noted, corrected and made it conform named by PersonalData under the person ids
 * Accounts gives them, then presented to the presenter it builds for the call (ADR 0084). A controller calls it after
 * a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ExchangeReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultExchange,
        private RefusalTranslatingHandler $consultMember,
        private RefusalTranslatingHandler $consultPersons,
        private RefusalTranslatingHandler $consultOrganization,
        private Links $links,
        private IntentLinks $intents,
    ) {
    }

    public function read(string $prospector, string $id): ExchangeDetailResource
    {
        $kept = new KeptExchange();
        ($this->consultExchange)(new ConsultExchange($prospector, $id), $kept);

        return $this->resourceOf($prospector, $kept->exchange);
    }

    /**
     * The exchange already read, its interlocutor and its authors named.
     */
    public function resourceOf(string $prospector, ExchangeDetailResponse $exchange): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->participantsOf($prospector, $exchange), $this->links, $this->intents);
        $presenter->present($exchange);

        return $presenter->resource;
    }

    private function participantsOf(string $prospector, ExchangeDetailResponse $exchange): ExchangeParticipants
    {
        $personOfMember = $this->personsOfMembers(array_filter([$exchange->createdBy, $exchange->correctedBy, $exchange->madeConformBy]));
        $persons = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($prospector, array_values(array_unique(array_filter([$exchange->personId, ...array_values($personOfMember)])))), $persons);

        return new ExchangeParticipants($this->interlocutorOf($exchange, $persons->persons), $persons->persons, $personOfMember);
    }

    /**
     * The contact it was with, or the organization itself, the only one it is kept for.
     *
     * @param array<string, PersonResponse> $persons by person id
     */
    private function interlocutorOf(ExchangeDetailResponse $exchange, array $persons): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization($this->organizationOf($exchange->organizationIds[0]))
            : InterlocutorResource::ofPerson($exchange->personId, $persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }

    private function organizationOf(string $id): OrganizationResponse
    {
        $kept = new KeptOrganization();
        ($this->consultOrganization)(new ConsultOrganization($id), $kept);

        return $kept->organization;
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
            $persons[$member] = $this->personOf($member);
        }

        return $persons;
    }

    private function personOf(string $member): string
    {
        $kept = new KeptMember();
        ($this->consultMember)(new ConsultMember($member), $kept);

        return $kept->member->personId;
    }
}
