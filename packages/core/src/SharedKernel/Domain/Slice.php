<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * A slice of a list, newest first, and where the next one starts (ADR 0049): a list is walked with a cursor,
 * never counted.
 *
 * @template T
 */
final readonly class Slice
{
    /**
     * @param list<T>     $content
     * @param string|null $next    the cursor of the next slice, null when this one is the last
     */
    public function __construct(
        public array $content,
        public ?string $next,
    ) {
    }
}
