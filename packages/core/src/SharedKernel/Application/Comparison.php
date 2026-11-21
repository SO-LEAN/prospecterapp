<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * What a client knows of a person or an organization, against what is noted (ADR 0177), by the names of the fields:
 * those it would fill, and those it gives otherwise than noted.
 */
final readonly class Comparison
{
    /**
     * @param list<string> $completed
     * @param list<string> $differing
     */
    public function __construct(public array $completed, public array $differing)
    {
    }
}
