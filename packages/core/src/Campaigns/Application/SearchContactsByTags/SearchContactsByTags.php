<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

/**
 * The contacts of the account of the member carrying every tag chosen, newest first, a slice after the cursor `after`
 * (ADR 0049), counted.
 */
final readonly class SearchContactsByTags
{
    /**
     * @param list<string> $tags as typed, written as tags
     */
    public function __construct(
        public string $memberId,
        public array $tags,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
