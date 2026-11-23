<?php

declare(strict_types=1);

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;

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
        #[OA\Property(description: 'The business refusal\'s name, for the client to word in its language (ADR 0073)', example: 'unknownOrganization')]
        public ?string $reason = null,
    ) {
    }
}
