<?php

declare(strict_types=1);

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;

/**
 * Where the browser sends one file itself, for a few minutes (ADR 0077). It posts a multipart form to `url`, with every
 * field of `fields`, then `Content-Type` (the file's media type), then the file in `file`.
 */
final readonly class UploadResource
{
    /**
     * @param array<string, string> $fields
     */
    public function __construct(
        #[OA\Property(description: 'The name the file is attached under once it is sent')]
        public string $upload,
        public string $url,
        #[OA\Property(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]
        public array $fields,
        #[OA\Property(description: 'The largest file the form accepts, in bytes')]
        public int $heaviest,
    ) {
    }
}
