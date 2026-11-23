<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

/**
 * Reads which of an account's contacts a key designates, without restoring them (ADR 0088). It reads the keys that
 * designate each contact in their account.
 */
interface DesignatedContactRetriever
{
    /**
     * Returns the contact who holds this link, as a current link or as a former one.
     */
    public function withReference(AccountId $accountId, ExternalReference $reference): ?PersonId;

    public function withEmail(AccountId $accountId, Email $email): ?PersonId;

    /**
     * Returns the contacts who have this phone number. Several contacts may share it.
     *
     * @return list<PersonId>
     */
    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array;

    /**
     * Returns the contacts who share this resemblance key, because they are named alike (ADR 0170).
     *
     * @return list<PersonId>
     */
    public function resembling(AccountId $accountId, PersonResemblanceKey $key): array;
}
