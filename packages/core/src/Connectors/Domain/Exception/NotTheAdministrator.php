<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * The clients of an account at the providers are the business of its administrators (ADR 0125).
 */
final class NotTheAdministrator extends NotAllowed
{
    public static function ofTheClients(): self
    {
        return self::because('Only an administrator of the account configures its clients');
    }
}
