<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A prospector erased an exchange from every timeline. Its attached files remain to be removed from the private
 * storage. Its last facts name its contact and the campaign it was logged in, if any (ADR 0152). The event also says
 * whether the exchange went with its organization, in which case no member erased it (ADR 0155).
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
        public ?ContactId $contact = null,
        public ?CampaignId $campaign = null,
        public bool $withItsOrganization = false,
    ) {
        parent::__construct($accountId, $by, $exchange, $occurredAt);
    }
}
