<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads an account's organizations by their email's domain, without restoring them (ADR 0088, 0146).
 */
interface ProposedOrganizationRetriever
{
    /**
     * Returns the first of the account's organizations whose email has this domain, in the order of their ids.
     */
    public function firstWithEmailDomain(AccountId $accountId, string $domain): ?ProposedOrganization;
}
