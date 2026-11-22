<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * An exchange noted first outside any campaign, by the import of a mailbox or an AI, became the exchange of a
 * recipient of a campaign, as the AI leading it noted the same sending (ADR 0195).
 */
final readonly class ExchangeJoinedCampaign extends ExchangeEvent
{
}
