<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\PushBackErasureOfContactToAttach\PushBackErasureOfContactToAttach;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange is planned, noted, moved or corrected, its moment may be a later meeting with its contact to
 * attach, which pushes their erasure back (ADR 0146).
 */
final readonly class PushBackErasureOfContactToAttachPolicy implements Policy
{
    public function __invoke(ExchangePlanned|ExchangeNoted|ExchangeMoved|ExchangeCorrected $event): PushBackErasureOfContactToAttach
    {
        return new PushBackErasureOfContactToAttach($event->exchange->value, $event->occurredAt);
    }
}
