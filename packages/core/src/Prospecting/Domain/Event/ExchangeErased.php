<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A prospector erased an exchange from every timeline, the files joined to it left to remove from the private
 * storage.
 */
final readonly class ExchangeErased extends ExchangeEvent
{
    /**
     * @param list<string> $files
     */
    public function __construct(AccountId $accountId, MemberId $by, ExchangeId $exchange, public array $files, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
