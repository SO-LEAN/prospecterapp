<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * What a client knows about a person or an organization, compared with what Prospecter holds (ADR 0177). It names
 * the fields that the client would fill, and the fields where the client's value differs from the saved one.
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
