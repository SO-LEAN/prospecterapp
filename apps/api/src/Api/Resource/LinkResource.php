<?php

namespace App\Api\Resource;

/**
 * What the client may do next: call `method` on `href`. Absent (null) when the action is not possible now.
 */
final readonly class LinkResource
{
    public function __construct(
        public string $href,
        public string $method,
    ) {
    }
}
