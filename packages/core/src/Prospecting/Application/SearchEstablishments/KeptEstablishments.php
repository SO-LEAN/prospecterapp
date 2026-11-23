<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchEstablishments;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptEstablishments implements SearchEstablishmentsOutput
{
    /** @var list<FoundEstablishment> */
    public private(set) array $establishments = [];

    public function present(array $establishments): void
    {
        $this->establishments = $establishments;
    }
}
