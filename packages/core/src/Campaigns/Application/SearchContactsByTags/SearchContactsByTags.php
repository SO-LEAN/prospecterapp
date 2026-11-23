<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

/**
 * The contacts of the member's account that carry every chosen tag, newest first, with their count. It returns a slice
 * after the cursor `after` (ADR 0049).
 */
final readonly class SearchContactsByTags
{
    /**
     * @param list<string> $tags as typed; each is written as a tag
     */
    public function __construct(
        public string $memberId,
        public array $tags,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
