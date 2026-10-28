<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Something that happened to an exchange with a contact.
 */
abstract readonly class ExchangeEvent extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public ExchangeId $exchange, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): ExchangeId
    {
        return $this->exchange;
    }
}
