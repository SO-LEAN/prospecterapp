<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Who took part in an exchange, as the other contexts know them. It holds the interlocutor and the number to call
 * them at. It also holds the persons of the members who logged, corrected and redacted the exchange.
 */
final readonly class ExchangeParticipants
{
    /**
     * @param array<string, PersonCard> $persons        by person id
     * @param array<string, string>     $personOfMember the person id of each member, by member id
     */
    public function __construct(
        public InterlocutorResource $interlocutor,
        public array $persons,
        public array $personOfMember,
        public ?string $interlocutorPhoneNumber,
    ) {
    }
}
