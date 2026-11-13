<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Who cancelled a planned exchange and when, and who called it off, when it was said.
 */
final readonly class Cancellation
{
    public function __construct(
        public MemberId $by,
        public \DateTimeImmutable $at,
        public ?Canceller $canceller,
    ) {
    }
}
