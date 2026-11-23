<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The id of a natural person whose personal data PersonalData keeps. The other contexts refer to the person by an id
 * of their own, with the same value, and never keep the data (ADR 0031, 0197).
 */
final readonly class PersonId extends Uuid
{
}
