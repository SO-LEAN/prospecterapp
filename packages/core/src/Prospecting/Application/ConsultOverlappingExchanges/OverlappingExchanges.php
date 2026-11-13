<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class OverlappingExchanges
{
    /**
     * @param list<ExchangeInTimeline> $exchanges the soonest first
     */
    public function __construct(public array $exchanges)
    {
    }
}
