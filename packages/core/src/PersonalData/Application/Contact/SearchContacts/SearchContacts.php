<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\SearchContacts;

/**
 * Gets the persons the reader's account prospects, newest first, in a slice after the cursor `after` (ADR 0049). The
 * search matches the beginning of their name. With the right to read personal data unmasked, it also matches the
 * beginning of their email (ADR 0129).
 */
final readonly class SearchContacts
{
    public function __construct(
        public string $readerId,
        public string $query = '',
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
