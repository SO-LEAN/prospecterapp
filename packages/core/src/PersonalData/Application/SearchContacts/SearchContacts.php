<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

/**
 * The persons the account of the reader prospects, newest first, a slice after the cursor `after` (ADR 0049);
 * searched by the beginning of their name, and of their email with the right to read them in clear (ADR 0129).
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
