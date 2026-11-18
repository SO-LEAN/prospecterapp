<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A prospector erased an exchange from every timeline, the files joined to it left to remove from the private
 * storage; the contact it was with and the campaign it was noted in, if any, named by its last facts (ADR 0152).
 */
final readonly class ExchangeErased extends ExchangeEvent
{
    /**
     * @param list<string> $files
     */
    public function __construct(
        AccountId $accountId,
        MemberId $by,
        ExchangeId $exchange,
        public array $files,
        \DateTimeImmutable $occurredAt,
        public ?PersonId $contact = null,
        public ?CampaignId $campaign = null,
    ) {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
