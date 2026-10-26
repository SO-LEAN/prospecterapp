<?php

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * Who took part in an exchange, read in the other contexts: its interlocutor, and the persons of the members who
 * noted, corrected and made it conform.
 */
final readonly class ExchangeParticipants
{
    /**
     * @param array<string, PersonResponse> $persons        by person id
     * @param array<string, string>         $personOfMember the person id of each member, by member id
     */
    public function __construct(
        public InterlocutorResource $interlocutor,
        public array $persons,
        public array $personOfMember,
    ) {
    }
}
