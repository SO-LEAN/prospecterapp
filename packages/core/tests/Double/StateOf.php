<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * The private state of an aggregate, as the adapter of the API reads it to write its document
 * (`Hydrator::stateOf()`): the in-memory retrievers and the scenarios read what was saved without the aggregate
 * exposing it (ADR 0088).
 */
final class StateOf
{
    /**
     * @return array<string, mixed>
     */
    public static function the(object $aggregate): array
    {
        return \Closure::bind(fn (): array => get_object_vars($this), $aggregate, $aggregate::class)();
    }
}
