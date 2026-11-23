<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the organizations' facts as the other contexts count them, without restoring the organizations (ADR 0088).
 * It is the only reading of these facts, for their event and for their service.
 */
interface OrganizationFactsRetriever
{
    /**
     * Returns the organization's facts as the command is about to write them. They are absent when the account no
     * longer prospects the organization.
     */
    public function of(OrganizationId $id): OrganizationFactsResponse;

    /**
     * Returns the facts of the account's organizations after the cursor, newest first (ADR 0049).
     *
     * @return Slice<OrganizationFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
