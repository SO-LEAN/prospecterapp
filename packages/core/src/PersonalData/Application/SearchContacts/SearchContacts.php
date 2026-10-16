<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

/**
 * The persons the account of the reader prospects, newest first, a slice after the cursor `after` (ADR 0049);
 * a text search needs the right to read them in clear.
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
