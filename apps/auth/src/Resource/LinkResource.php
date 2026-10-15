<?php

namespace App\Resource;

final readonly class LinkResource
{
    public function __construct(
        public string $href,
        public string $method,
    ) {
    }
}
