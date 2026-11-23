<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Who erased an exchange, and when. Its fingerprint keeps this much of it (ADR 0166).
 */
final readonly class Erasure
{
    public function __construct(
        public MemberId $by,
        public \DateTimeImmutable $at,
    ) {
    }
}
