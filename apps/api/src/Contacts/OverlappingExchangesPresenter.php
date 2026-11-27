<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\ConsultOverlappingExchangesOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\OverlappingExchanges;

/**
 * Presents what Prospecting shows of the signed-in member's exchanges that overlap a slot: each exchange with its
 * links, and who it is with, by ids. One presenter serves one call (ADR 0085, 0217).
 */
final class OverlappingExchangesPresenter implements ConsultOverlappingExchangesOutput
{
    public private(set) OverlappingExchanges $overlapping;

    /** @var array<string, ExchangeResource> by exchange id */
    public private(set) array $exchanges = [];

    public function __construct(private readonly TimelineResourceFactory $timelineResourceFactory)
    {
    }

    public function present(OverlappingExchanges $overlapping): void
    {
        $this->overlapping = $overlapping;

        foreach ($overlapping->exchanges as $exchange) {
            $this->exchanges[$exchange->id] = $this->timelineResourceFactory->createExchange($exchange);
        }
    }
}
