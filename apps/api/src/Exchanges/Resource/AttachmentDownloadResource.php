<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use OpenApi\Attributes as OA;

/**
 * Where to read a file attached to an exchange. The file downloads under its name (ADR 0077).
 */
final readonly class AttachmentDownloadResource
{
    public function __construct(
        public string $name,
        #[OA\Property(description: 'Signed for one minute. Open it at once and never keep it')]
        public string $url,
    ) {
    }
}
