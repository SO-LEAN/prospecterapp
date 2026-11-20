<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;

/**
 * A link to a tool outside, as the use cases answer it: its source, the identifier of what it designates there, and
 * the address that opens it.
 */
final readonly class ExternalLink
{
    public function __construct(
        public string $source,
        public string $identifier,
        public string $address,
    ) {
    }

    /**
     * A link read masked: its stars in place of its source, its identifier and its address.
     */
    public static function masked(string $stars): self
    {
        return new self($stars, $stars, $stars);
    }

    /**
     * @return list<self>
     */
    public static function allOf(ExternalReferences $references): array
    {
        return array_map(static fn (ExternalReference $reference): self => new self($reference->source, $reference->identifier, $reference->address), $references->all());
    }
}
