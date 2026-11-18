<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the organizations of an account by the domain of their email, without restoring them (ADR 0088, 0146).
 */
interface ProposedOrganizationRetriever
{
    /**
     * The first, by identity, of the organizations of the account whose email has this domain.
     */
    public function firstWithEmailDomain(AccountId $accountId, string $domain): ?ProposedOrganization;
}
