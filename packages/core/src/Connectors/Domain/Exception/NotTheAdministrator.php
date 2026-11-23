<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * Only an account's administrators manage the account's clients at the providers (ADR 0125).
 */
final class NotTheAdministrator extends NotAllowed
{
    public static function ofTheClients(): self
    {
        return self::because('Only an administrator of the account configures its clients');
    }
}
