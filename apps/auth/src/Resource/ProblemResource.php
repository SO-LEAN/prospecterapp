<?php

namespace App\Resource;

/**
 * RFC 9457 problem details, the shape of every API error (same as Symfony validation errors).
 */
final readonly class ProblemResource
{
    /**
     * @param list<ViolationResource> $violations
     */
    public function __construct(
        public string $title,
        public int $status,
        public string $detail,
        public array $violations = [],
        public string $type = 'about:blank',
    ) {
    }
}
