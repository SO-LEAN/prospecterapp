<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

/**
 * Reads, without restoring them (ADR 0088), the organizations located before their municipality was kept, so that
 * `LocateAgain` gives it to each, once (ADR 0142).
 */
interface OrganizationsToLocateAgainRetriever
{
    /**
     * @return iterable<string> the identities of the organizations of every account located without their municipality
     */
    public function all(): iterable;
}
