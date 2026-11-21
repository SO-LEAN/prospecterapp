<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The import of an e-mail joined one of its files to the exchange it noted, an upload left to keep in the private
 * storage once the exchange is written (ADR 0182).
 */
final readonly class AttachmentImported extends ExchangeEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ExchangeId $exchange, public AttachmentId $attachment, public string $file, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
