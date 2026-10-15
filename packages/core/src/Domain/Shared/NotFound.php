<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

/**
 * Something the request names does not exist, or is not reachable by the one asking: they cannot tell
 * the difference (ADR 0019).
 */
abstract class NotFound extends \RuntimeException
{
}
