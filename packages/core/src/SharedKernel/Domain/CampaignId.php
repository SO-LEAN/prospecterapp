<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * A campaign's id. Campaigns publishes it to the other contexts. A Prospecting exchange keeps the id of the campaign
 * it was logged in (ADR 0145).
 */
final readonly class CampaignId extends Uuid
{
}
