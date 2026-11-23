<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

/**
 * Reads the organizations located before Prospecter kept their municipality, without restoring them (ADR 0088).
 * `LocateAgain` then gives each of them its municipality once (ADR 0142).
 */
interface OrganizationsToLocateAgainRetriever
{
    /**
     * @return iterable<string> the ids of the organizations located without their municipality, in every account
     */
    public function all(): iterable;
}
