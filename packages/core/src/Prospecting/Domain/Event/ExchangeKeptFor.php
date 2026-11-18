<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The contact to attach of the exchange was attached to an organization: the exchange is kept for it.
 */
final readonly class ExchangeKeptFor extends ExchangeEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ExchangeId $exchange, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
