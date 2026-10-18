<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * Only the person, or a member allowed to manage personal data, may change them.
 */
final class ThePersonOrAPersonalDataManagerOnly extends NotAllowed
{
}
