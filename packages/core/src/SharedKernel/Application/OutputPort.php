<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * What a query presents its answer to (ADR 0085): each query declares its own, extending this one. An implementation
 * keeps the state of one call, its view model: it is no service, its client builds it where it uses it and reads it
 * once the query answered. A Contract imports nothing (ADR 0047): the output ports of the published queries extend
 * nothing.
 */
interface OutputPort
{
}
