<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * The exchange an account logged under a fingerprint. It is kept after the exchange is erased, with who erased it
 * (ADR 0166).
 */
final readonly class Fingerprinted
{
    public function __construct(
        public ExchangeId $exchange,
        public ?Erasure $erasure = null,
    ) {
    }
}
