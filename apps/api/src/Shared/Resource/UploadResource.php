<?php

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;

/**
 * Where the browser sends one file itself, for a few minutes (ADR 0077): a form posted as multipart to `url`, with
 * every field of `fields`, then `Content-Type` (the media type of the file), then the file in `file`.
 */
final readonly class UploadResource
{
    /**
     * @param array<string, string> $fields
     */
    public function __construct(
        #[OA\Property(description: 'What the file is joined under once sent')]
        public string $upload,
        public string $url,
        #[OA\Property(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]
        public array $fields,
        #[OA\Property(description: 'The heaviest file the form takes, in bytes')]
        public int $heaviest,
    ) {
    }
}
