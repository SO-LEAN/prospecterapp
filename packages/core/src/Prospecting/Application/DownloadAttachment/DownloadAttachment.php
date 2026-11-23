<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

/**
 * Asks for a link that reads a file attached to an exchange. The link works for a moment only.
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
