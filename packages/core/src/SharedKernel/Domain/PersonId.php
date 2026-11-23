<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The id of a natural person whose personal data PersonalData keeps, published to the other contexts. They keep
 * this id and never the data. Prospecting exchanges' text and attachments are the exception (ADR 0031, 0074, 0077).
 */
final readonly class PersonId extends Uuid
{
}
