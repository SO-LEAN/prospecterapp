<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\PutTags;

final readonly class PutTags
{
    /**
     * @param string       $holder the kind of what is tagged: `organization` or `contact`
     * @param list<string> $tags   as the member typed them
     */
    public function __construct(
        public string $memberId,
        public string $holder,
        public string $holderId,
        public array $tags,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
