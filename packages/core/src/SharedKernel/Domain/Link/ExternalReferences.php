<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

/**
 * A person's or an organization's links: each reference once, and a few at most. They are optional: a record may
 * have none.
 */
final readonly class ExternalReferences
{
    public const int MAX = 5;

    /**
     * @param list<ExternalReference> $references
     */
    private function __construct(private array $references)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param list<string> $addresses
     *
     * @throws NotAWebAddress|ReferenceOfAnotherKind|UnrecognizedPage|TooManyLinks
     */
    public static function designating(array $addresses, Designated $designated): self
    {
        return self::of(array_map(static fn (string $address): ExternalReference => ExternalReference::designating($address, $designated), $addresses));
    }

    /**
     * Keeps each reference once: the first one given wins.
     *
     * @param list<ExternalReference> $references
     *
     * @throws TooManyLinks
     */
    public static function of(array $references): self
    {
        $once = [];

        foreach ($references as $reference) {
            $once[$reference->key()] ??= $reference;
        }

        if (count($once) > self::MAX) {
            throw TooManyLinks::beyond(self::MAX);
        }

        return new self(array_values($once));
    }

    public function holds(ExternalReference $reference): bool
    {
        return array_any($this->references, static fn (ExternalReference $held): bool => $held->key() === $reference->key());
    }

    /**
     * The given references that these links do not hold, each once (ADR 0177).
     *
     * @param list<ExternalReference> $given
     *
     * @return list<ExternalReference>
     */
    public function notHeldAmong(array $given): array
    {
        $notHeld = [];

        foreach ($given as $reference) {
            if (!$this->holds($reference)) {
                $notHeld[$reference->key()] ??= $reference;
            }
        }

        return array_values($notHeld);
    }

    /**
     * The given references that these links do not hold, as many as there is room for beside them (ADR 0177).
     *
     * @param list<ExternalReference> $given
     *
     * @return list<ExternalReference>
     */
    public function roomFor(array $given): array
    {
        return array_slice($this->notHeldAmong($given), 0, self::MAX - count($this->references));
    }

    /**
     * Whether one of the links comes from this source. For example, a member reaches a person on LinkedIn through
     * their profile there.
     */
    public function hasOneOf(KnownSource $source): bool
    {
        return array_any($this->references, static fn (ExternalReference $reference): bool => $reference->source === $source->value);
    }

    /**
     * @return list<ExternalReference>
     */
    public function all(): array
    {
        return $this->references;
    }
}
