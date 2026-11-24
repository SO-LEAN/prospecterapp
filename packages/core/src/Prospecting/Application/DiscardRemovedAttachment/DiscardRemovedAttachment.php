<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment;

final readonly class DiscardRemovedAttachment
{
    /**
     * @param string $file the attachment's file in the private storage
     */
    public function __construct(
        public string $file,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
