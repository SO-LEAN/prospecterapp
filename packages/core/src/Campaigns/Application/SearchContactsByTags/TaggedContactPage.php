<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

/**
 * A slice of the contacts carrying every tag chosen, and how many carry them in all.
 */
final readonly class TaggedContactPage
{
    /**
     * @param list<string> $contactIds newest first
     * @param string|null  $next       the cursor of the next slice, null when this one is the last
     */
    public function __construct(
        public array $contactIds,
        public int $found,
        public ?string $next,
    ) {
    }
}
