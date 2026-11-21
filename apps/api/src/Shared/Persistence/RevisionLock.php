<?php

namespace App\Shared\Persistence;

/**
 * The optimistic lock of an aggregate (ADR 0181): its item is written only while it is as its unit of work read it,
 * at the same revision, or still absent.
 */
final readonly class RevisionLock
{
    /**
     * @param array<string, mixed> $values the values the condition names
     */
    private function __construct(public string $condition, public array $values)
    {
    }

    /**
     * @param array<string, mixed>|null $read the item as the unit read it, null when it was absent
     */
    public static function on(?array $read): self
    {
        if (null === $read) {
            return new self('attribute_not_exists(PK)', []);
        }

        if (!isset($read[Revision::ATTRIBUTE])) {
            return new self('attribute_not_exists('.Revision::ATTRIBUTE.')', []);
        }

        return new self(Revision::ATTRIBUTE.' = :read', [':read' => Revision::of($read)]);
    }
}
