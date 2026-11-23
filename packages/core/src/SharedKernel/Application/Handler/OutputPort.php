<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

/**
 * The port a query presents its answer to (ADR 0085). Each query declares its own port, which extends this one. An
 * implementation keeps one call's state, its view model. It is not a service: its client builds it where it uses
 * it, and reads it once the query has answered. A Contract imports nothing (ADR 0047), so the published queries'
 * output ports extend nothing.
 */
interface OutputPort
{
}
