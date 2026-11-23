<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What a client knows about a person or an organization, compared with what Prospecter holds (ADR 0177). It lists
 * the empty fields that the client would fill. It also lists the fields where the client's value differs from the
 * saved one. Those fields stay as they are.
 */
final readonly class Completion
{
    /**
     * @param list<string> $completed the fields' names
     * @param list<string> $differing the fields' names
     */
    public function __construct(public array $completed = [], public array $differing = [])
    {
    }

    /**
     * A field that holds a value keeps it. An empty field takes the given value.
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
     * A field with several values, such as the links. The given values that the field does not hold yet fill it
     * while there is room. The others differ.
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
