<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $content
     */
    public function __construct(
        public int $number,
        public int $total,
        public int $totalPages,
        public array $content,
    ) {
    }
}
