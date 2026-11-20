<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\TooManyLinks;

/**
 * The links of a person or of an organization, each reference once, a few at most: they are optional, a sheet may have
 * none.
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

    /**
     * @param list<string> $addresses
     */
    public static function designating(array $addresses, Designated $designated): self
    {
        return self::of(array_map(static fn (string $address): ExternalReference => ExternalReference::designating($address, $designated), $addresses));
    }

    /**
     * Each reference once, the first given kept.
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

    /**
     * @return list<ExternalReference>
     */
    public function all(): array
    {
        return $this->references;
    }
}
