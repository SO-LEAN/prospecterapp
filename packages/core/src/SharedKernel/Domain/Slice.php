<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * A slice of a list, newest first, with the cursor of the next slice (ADR 0049). A client walks a list with the
 * cursor and never counts it.
 *
 * @template T
 */
final readonly class Slice
{
    /**
     * @param list<T>     $content
     * @param string|null $next    the next slice's cursor, null when this slice is the last one
     */
    public function __construct(
        public array $content,
        public ?string $next,
    ) {
    }
}
