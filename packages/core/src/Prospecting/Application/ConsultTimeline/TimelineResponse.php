<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class TimelineResponse
{
    /**
     * @param list<ExchangeResponse> $exchanges
     * @param string|null            $next      the cursor of the next slice, null when this one is the last
     */
    private function __construct(
        public array $exchanges,
        public ?string $next,
    ) {
    }

    /**
     * @param Slice<Exchange> $slice
     */
    public static function of(Slice $slice): self
    {
        return new self(array_map(ExchangeResponse::of(...), $slice->content), $slice->next);
    }
}
