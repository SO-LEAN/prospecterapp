<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads which contact of an account a key designates, by what designates each one in its account, without restoring
 * them (ADR 0088).
 */
interface DesignatedContactRetriever
{
    /**
     * The contact holding this link, as one of their own or as a former one.
     */
    public function withReference(AccountId $accountId, ExternalReference $reference): ?PersonId;

    public function withEmail(AccountId $accountId, Email $email): ?PersonId;

    /**
     * The contacts having this phone number: several may share it.
     *
     * @return list<PersonId>
     */
    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array;

    /**
     * The contacts sharing this resemblance key: named alike (ADR 0170).
     *
     * @return list<PersonId>
     */
    public function resembling(AccountId $accountId, PersonResemblanceKey $key): array;
}
