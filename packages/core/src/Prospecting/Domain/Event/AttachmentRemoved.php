<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A prospector removed a file joined to an exchange.
 */
final readonly class AttachmentRemoved extends ExchangeEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ExchangeId $exchange, public AttachmentId $attachment, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
