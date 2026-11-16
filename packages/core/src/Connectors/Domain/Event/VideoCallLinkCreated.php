<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The calendar of the member at the provider created the link of a video call they hold, which asked one (ADR 0137).
 */
final readonly class VideoCallLinkCreated extends ConnectionEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public ExchangeId $exchange, public string $link, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
