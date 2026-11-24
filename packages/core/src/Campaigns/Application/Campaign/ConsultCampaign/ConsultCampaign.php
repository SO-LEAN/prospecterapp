<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign;

/** A campaign as the member reads it. Its recipients processed today are counted on the member's clock. */
final readonly class ConsultCampaign
{
    /**
     * @param string $timeZone the IANA name of the member's clock
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $timeZone,
        public \DateTimeImmutable $at,
    ) {
    }
}
