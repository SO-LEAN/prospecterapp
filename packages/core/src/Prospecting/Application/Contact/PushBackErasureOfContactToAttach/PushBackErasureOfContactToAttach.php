<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\PushBackErasureOfContactToAttach;

final readonly class PushBackErasureOfContactToAttach
{
    /**
     * @param string $exchangeId the exchange whose moment may be a new meeting with its contact to attach
     */
    public function __construct(
        public string $exchangeId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
