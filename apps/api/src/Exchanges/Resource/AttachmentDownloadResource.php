<?php

namespace App\Exchanges\Resource;

use OpenApi\Attributes as OA;

/**
 * Where to read a file joined to an exchange, downloaded under its name (ADR 0077).
 */
final readonly class AttachmentDownloadResource
{
    public function __construct(
        public string $name,
        #[OA\Property(description: 'Signed for a minute: open it at once, never keep it')]
        public string $url,
    ) {
    }
}
