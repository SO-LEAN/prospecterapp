<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Port;

/**
 * The facts being recorded while a rebuild starts: one may have read the live versions before the new one was
 * started, and would record its fact in the active version only.
 */
interface RecordingsInFlight
{
    /**
     * Returns once every recording begun before the call has ended.
     */
    public function awaitTheirEnd(): void;
}
