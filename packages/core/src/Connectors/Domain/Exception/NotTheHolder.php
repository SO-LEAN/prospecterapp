<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * An exchange is pushed into the calendar of the member who planned it. A colleague cannot push it for them.
 */
final class NotTheHolder extends NotAllowed
{
    public static function ofTheExchange(): self
    {
        return self::because('Only the holder of the exchange pushes it');
    }
}
