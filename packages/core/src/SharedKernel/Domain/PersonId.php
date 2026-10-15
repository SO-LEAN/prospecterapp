<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The identity of a natural person whose personal data PersonalData keeps, published to the other contexts:
 * they keep this id, never the data (ADR 0031).
 */
final readonly class PersonId extends Uuid
{
}
