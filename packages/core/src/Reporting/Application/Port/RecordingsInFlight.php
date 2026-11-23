<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Port;

/**
 * The facts being recorded when a rebuild starts. A recording may have read the live versions before the new version
 * started. It would then record its fact in the active version only.
 */
interface RecordingsInFlight
{
    /**
     * Returns once every recording begun before the call has ended.
     */
    public function awaitTheirEnd(): void;
}
