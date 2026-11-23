<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * An aggregate's private state, as the API's adapter reads it to write its document (`Hydrator::stateOf()`). The
 * in-memory retrievers and the scenarios read what was saved without the aggregate exposing it (ADR 0088).
 */
final class StateOf
{
    public static function the(object $aggregate): State
    {
        return new State(\Closure::bind(fn (): array => get_object_vars($this), $aggregate, $aggregate::class)());
    }
}
