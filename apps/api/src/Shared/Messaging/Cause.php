<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * Why an event is published. It holds the command that started the chain (its correlation), the event that caused it
 * (null for the command itself), and the number of reactions between the event and the command.
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
