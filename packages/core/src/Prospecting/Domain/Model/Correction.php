<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Who corrected an exchange, and when.
 */
final readonly class Correction
{
    public function __construct(
        public MemberId $by,
        public \DateTimeImmutable $at,
    ) {
    }
}
