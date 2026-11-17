<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The identity of a campaign of Campaigns, published to the other contexts: an exchange of Prospecting keeps the campaign
 * it was noted in by it (ADR 0145).
 */
final readonly class CampaignId extends Uuid
{
}
