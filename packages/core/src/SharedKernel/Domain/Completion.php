<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What a client knows of a person or an organization, against what is noted (ADR 0177): the fields it would fill,
 * empty until then, and those it gives otherwise than noted, which stay as they are.
 */
final readonly class Completion
{
    /**
     * @param list<string> $completed the names of the fields
     * @param list<string> $differing the names of the fields
     */
    public function __construct(public array $completed = [], public array $differing = [])
    {
    }

    /**
     * A field noted keeps its value; one left empty takes the value given.
     */
    public function with(string $field, ?string $noted, ?string $given): self
    {
        return match (true) {
            null === $given, $noted === $given => $this,
            null === $noted => new self([...$this->completed, $field], $this->differing),
            default => new self($this->completed, [...$this->differing, $field]),
        };
    }

    /**
     * A field of several values, links: those given not held yet fill it while there is room, the others differ.
     */
    public function withSome(string $field, int $filled, int $left): self
    {
        return new self(
            0 < $filled ? [...$this->completed, $field] : $this->completed,
            0 < $left ? [...$this->differing, $field] : $this->differing,
        );
    }

    public function completes(string $field): bool
    {
        return in_array($field, $this->completed, true);
    }

    public function completesAnything(): bool
    {
        return [] !== $this->completed;
    }
}
