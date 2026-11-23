<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;

/**
 * Reads persons' cards without restoring the persons (ADR 0088). Each card is read as its reader may read it, unmasked
 * or masked (`PersonReading`, `Masking`). It tells what the reader may do with the person (`PersonPossibilities`).
 */
interface PersonCardRetriever
{
    /**
     * @throws UnknownPerson when the person belongs to another account: the reader must not even learn that they exist
     */
    public function get(PersonId $id, Reader $reader): PersonCard;

    /**
     * Returns the persons who exist among these ids and whom the reader reaches, in no particular order.
     *
     * @param list<PersonId> $ids
     *
     * @return list<PersonCard>
     */
    public function ofIds(array $ids, Reader $reader): array;

    /**
     * Returns the contacts of the reader's account who have this phone number.
     *
     * @return list<PersonCard>
     */
    public function contactsWithPhoneNumber(PhoneNumber $phoneNumber, Reader $reader): array;
}
