<?php

namespace App\Shared\Messaging;

/**
 * Why an event is published: the command it all started from (its correlation), the event that caused it (null
 * for the command itself), and how many reactions separate it from the command.
 */
final readonly class Cause
{
    public function __construct(public string $correlationId, public ?string $causationId, public int $depth)
    {
    }

    public static function ofACommand(string $correlationId): self
    {
        return new self($correlationId, null, 0);
    }
}
