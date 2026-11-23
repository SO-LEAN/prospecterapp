<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Who cancelled a planned exchange, and when. It also holds who called it off, when the prospector said so.
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
