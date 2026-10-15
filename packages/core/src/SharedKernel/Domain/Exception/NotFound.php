<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * Unknown, or not reachable by who asks: they do not even learn that it exists.
 */
abstract class NotFound extends BusinessException
{
}
