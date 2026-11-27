<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: the account's organization an address designates (ADR 0089, 0154, 0223). It is translated from Prospecting's
 * published service `WhichOrganizationsHaveTheseEmailsService`. The address never leaves the question.
 */
interface OrganizationCorrespondents
{
    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId;
}
