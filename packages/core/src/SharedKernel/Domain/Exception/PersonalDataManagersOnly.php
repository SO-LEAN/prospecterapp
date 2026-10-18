<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * Only a member allowed to manage personal data may carry out this intention.
 */
final class PersonalDataManagersOnly extends NotAllowed
{
}
