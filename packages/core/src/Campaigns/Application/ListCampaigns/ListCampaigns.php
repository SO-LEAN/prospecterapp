<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ListCampaigns;

/**
 * The campaigns of the team of the member, whole, each with the contacts it targets counted (ADR 0144).
 */
final readonly class ListCampaigns
{
    public function __construct(public string $memberId)
    {
    }
}
