<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: who of the account an address designates, translated from the contexts that know them, PersonalData for the
 * contacts and Prospecting for the organizations, through their published services (ADR 0089, 0154). The address
 * never leaves the question.
 */
interface Correspondents
{
    public function contactWithEmail(AccountId $accountId, Email $email): ?PersonId;

    public function organizationWithEmail(AccountId $accountId, Email $email): ?OrganizationId;
}
