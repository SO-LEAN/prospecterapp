<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: who in the account an address designates (ADR 0089, 0154). It is translated from the contexts that know the
 * correspondents, through their published services: PersonalData for the contacts, Prospecting for the
 * organizations. The address never leaves the question.
 */
interface Correspondents
{
    public function contactWithEmail(AccountId $accountId, Email $email): ?PersonId;

    public function organizationWithEmail(AccountId $accountId, Email $email): ?OrganizationId;
}
