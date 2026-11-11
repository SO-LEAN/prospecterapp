<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the contacts as the other contexts count them, without restoring them (ADR 0088): the one
 * reading of them, for their event and for their service.
 */
interface ContactFactsRetriever
{
    /**
     * The facts of the person as the command is about to write them, absent once it is forgotten; null for a
     * person kept for another purpose than prospecting, the profile of a member, which is no contact.
     */
    public function of(PersonId $id): ?ContactFactsResponse;

    /**
     * The facts of the contacts of the account newest first, after the cursor (ADR 0049).
     *
     * @return Slice<ContactFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
