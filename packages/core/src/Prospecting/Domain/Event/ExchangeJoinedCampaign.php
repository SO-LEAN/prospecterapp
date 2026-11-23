<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A mailbox's import or an AI first logged an exchange outside any campaign. The exchange then became a campaign
 * recipient's exchange, because the AI running the campaign logged the same sending (ADR 0195).
 */
final readonly class ExchangeJoinedCampaign extends ExchangeEvent
{
}
