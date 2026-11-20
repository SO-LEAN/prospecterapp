<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * The exchange an account noted under a fingerprint, kept once it is erased, with who erased it (ADR 0166).
 */
final readonly class Fingerprinted
{
    public function __construct(
        public ExchangeId $exchange,
        public ?Erasure $erasure = null,
    ) {
    }
}
