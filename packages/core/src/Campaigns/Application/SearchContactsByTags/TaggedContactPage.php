<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

/** A slice of the contacts that carry every chosen tag, and how many contacts carry them in total. */
final readonly class TaggedContactPage
{
    /**
     * @param list<string> $contactIds newest first
     * @param string|null  $next       the next slice's cursor, null when this one is the last
     */
    public function __construct(
        public array $contactIds,
        public int $found,
        public ?string $next,
    ) {
    }
}
