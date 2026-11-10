<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the organizations as the other contexts count them, without restoring them (ADR 0088): the
 * one reading of them, for their event and for their service.
 */
interface OrganizationFactsRetriever
{
    /**
     * The facts of the organization as the command is about to write them, absent when the account no longer
     * prospects it.
     */
    public function of(OrganizationId $id): OrganizationFactsResponse;

    /**
     * The facts of the organizations of the account newest first, after the cursor (ADR 0049).
     *
     * @return Slice<OrganizationFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
