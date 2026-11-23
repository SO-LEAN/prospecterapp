<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;

/** A campaign's daily cap as the member sets it. The core decides what makes a valid cap. */
final readonly class DailyCapInput
{
    public function __construct(
        #[OA\Property(description: 'The most recipients processed in a day. It is a guard: an AI stops at it, and the member may go beyond it. Null takes the cap off')]
        public ?int $dailyCap = null,
    ) {
    }
}
