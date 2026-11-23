<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Response;

use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;

/**
 * A link to an outside tool, as the use cases return it: its source, the identifier of what it points to there, and
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
     * A masked link: stars replace its source, its identifier and its address.
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
