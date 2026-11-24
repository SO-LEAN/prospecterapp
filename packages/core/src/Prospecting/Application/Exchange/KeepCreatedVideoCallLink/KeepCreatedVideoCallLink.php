<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\KeepCreatedVideoCallLink;

final readonly class KeepCreatedVideoCallLink
{
    /**
     * @param string $link the link the holder's calendar created for the video call
     */
    public function __construct(
        public string $exchangeId,
        public string $link,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
