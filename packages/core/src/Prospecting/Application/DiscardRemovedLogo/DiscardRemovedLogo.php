<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedLogo;

final readonly class DiscardRemovedLogo
{
    /**
     * @param string $logo the URL of the logo's file in the storage
     */
    public function __construct(
        public string $logo,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
