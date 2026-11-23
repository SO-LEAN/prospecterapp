<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ListCampaigns;

/** Every campaign of the member's team, each with the count of contacts it targets (ADR 0144). */
final readonly class ListCampaigns
{
    public function __construct(public string $memberId)
    {
    }
}
