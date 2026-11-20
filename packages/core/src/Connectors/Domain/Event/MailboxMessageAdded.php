<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A message was added to the mailbox of the member, named by its id at the provider only, to import in a reaction of
 * its own so that its text travels alone (ADR 0154).
 */
final readonly class MailboxMessageAdded extends ConnectionEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public string $messageId, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
