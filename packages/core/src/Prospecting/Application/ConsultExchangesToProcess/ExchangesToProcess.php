<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class ExchangesToProcess
{
    /**
     * @param list<ExchangeInTimeline>    $exchanges       those the prospector planned, ended and still planned, the
     *                                                     oldest first
     * @param array<string, list<string>> $possibleIntents by exchange id, the names of what each offers its holder
     *                                                     now: MarkExchangeDone, MarkExchangeMissed, MoveExchange,
     *                                                     CancelExchange...
     */
    public function __construct(public array $exchanges, public array $possibleIntents)
    {
    }
}
