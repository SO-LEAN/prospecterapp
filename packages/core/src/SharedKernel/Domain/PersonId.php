<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The identity of a natural person whose personal data PersonalData keeps, published to the other contexts:
 * they keep this id, never the data, but the text and the attachments of the exchanges of Prospecting (ADR 0031, 0074, 0077).
 */
final readonly class PersonId extends Uuid
{
}
