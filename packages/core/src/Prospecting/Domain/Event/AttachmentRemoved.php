<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A prospector removed a file joined to an exchange, left to remove from the private storage.
 */
final readonly class AttachmentRemoved extends ExchangeEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ExchangeId $exchange, public AttachmentId $attachment, public string $file, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
