<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the cards of the persons without restoring them (ADR 0088), as their reader may read them: in clear or
 * masked (`PersonReading`, `Masking`), with what the reader may do of them (`PersonPossibilities`).
 */
interface PersonCardRetriever
{
    /**
     * @throws UnknownPerson for a person of another account, who does not even learn that they exist
     */
    public function get(PersonId $id, Reader $reader): PersonCard;

    /**
     * The persons that exist among these ids and the reader reaches, in no particular order.
     *
     * @param list<PersonId> $ids
     *
     * @return list<PersonCard>
     */
    public function ofIds(array $ids, Reader $reader): array;

    /**
     * The contacts of the account of the reader who have this phone number.
     *
     * @return list<PersonCard>
     */
    public function contactsWithPhoneNumber(PhoneNumber $phoneNumber, Reader $reader): array;
}
