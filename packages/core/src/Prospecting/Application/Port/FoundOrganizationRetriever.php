<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

/**
 * Reads the organizations an account prospects as a list shows them, without restoring them (ADR 0088).
 */
interface FoundOrganizationRetriever
{
    /**
     * Returns the account's organizations after the cursor, newest first (ADR 0049). When a search is given, it finds
     * them by the beginning of their name or of their email (ADR 0071).
     */
    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): FoundOrganizationPage;
}
