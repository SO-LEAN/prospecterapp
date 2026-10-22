<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

/**
 * A link reading a file joined to an exchange for a moment.
 */
final readonly class DownloadAttachment
{
    public function __construct(
        public string $prospectorId,
        public string $exchangeId,
        public string $id,
    ) {
    }
}
