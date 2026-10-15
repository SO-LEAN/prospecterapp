<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

/**
 * The persons the account of the reader prospects, newest first; a text search needs the right to read them
 * in clear.
 */
final readonly class SearchContacts
{
    public function __construct(
        public string $readerId,
        public int $page = 1,
        public string $query = '',
        public int $maxByPage = 20,
    ) {
    }
}
