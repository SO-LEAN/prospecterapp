<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: the account's contact an address designates (ADR 0089, 0154, 0223). It is translated from PersonalData's
 * published service `WhichContactsHaveTheseEmailsService`. The address never leaves the question.
 */
interface ContactCorrespondents
{
    public function withEmail(AccountId $accountId, Email $email): ?ContactId;
}
