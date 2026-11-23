<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the contacts' facts as the other contexts count them, without restoring the contacts (ADR 0088). It is the
 * only reading of these facts, for their event and for their service.
 */
interface ContactFactsRetriever
{
    /**
     * Returns the person's facts as the command is about to write them. Once the person is forgotten, their facts are
     * absent. Returns null for a person kept for a purpose other than prospecting: a member's profile is not a contact.
     */
    public function of(PersonId $id): ?ContactFactsResponse;

    /**
     * Returns the facts of the account's contacts, newest first, after the cursor (ADR 0049).
     *
     * @return Slice<ContactFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
