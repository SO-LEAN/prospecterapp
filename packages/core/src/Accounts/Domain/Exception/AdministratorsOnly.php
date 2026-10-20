<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * Only an administrator of the account may carry out this intention.
 */
final class AdministratorsOnly extends NotAllowed
{
}
