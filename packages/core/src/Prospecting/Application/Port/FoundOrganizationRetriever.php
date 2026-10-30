<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * Reads the organizations an account prospects as a list shows them, without restoring them (ADR 0088).
 */
interface FoundOrganizationRetriever
{
    /**
     * The organizations of the account newest first, found by the beginning of their name or of their email
     * when a search is given, after the cursor (ADR 0049, 0071).
     */
    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): FoundOrganizationPage;
}
