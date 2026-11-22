<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;

/**
 * The daily cap of a campaign as the member sets it: the core decides what makes one.
 */
final readonly class DailyCapInput
{
    public function __construct(
        #[OA\Property(description: 'The most recipients processed a day, a guard an AI stops at and the member may go beyond; none takes the cap off')]
        public ?int $dailyCap = null,
    ) {
    }
}
