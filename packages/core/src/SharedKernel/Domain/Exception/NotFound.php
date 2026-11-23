<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The aggregate is unknown, or the caller cannot reach it. The caller does not even learn that it exists.
 */
abstract class NotFound extends BusinessException
{
}
