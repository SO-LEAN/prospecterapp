<?php

namespace App\Exchanges;

use App\Exchanges\Resource\ExchangeDetailResource;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * An exchange as the API shows it: read in Prospecting, its contact and the members who noted and corrected it
 * named by PersonalData under the person ids Accounts gives them, then presented. A controller calls it after a
 * command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ExchangeReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultExchange,
        private RefusalTranslatingHandler $consultMember,
        private RefusalTranslatingHandler $consultPersons,
        private ExchangeDetailPresenter $presenter,
    ) {
    }

    public function read(string $prospector, string $id): ExchangeDetailResource
    {
        $exchange = ($this->consultExchange)(new ConsultExchange($prospector, $id));
        $personOfMember = $this->personsOfMembers(array_filter([$exchange->createdBy, $exchange->correctedBy]));
        $persons = ($this->consultPersons)(new ConsultPersons($prospector, array_values(array_unique([$exchange->personId, ...array_values($personOfMember)]))));

        return $this->presenter->present($exchange, $persons, $personOfMember);
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
