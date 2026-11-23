<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use OpenApi\Attributes as OA;

/**
 * A campaign's daily cap, as the AI running the campaign reads it. It is a guard: the AI stops there to ask its member.
 */
final readonly class DailyCapResource
{
    public function __construct(
        #[OA\Property(description: 'The most recipients processed in a day')]
        public int $sends,
        #[OA\Property(description: 'The recipients processed today, on the member\'s clock')]
        public int $processedToday,
        #[OA\Property(description: 'Whether today\'s processed recipients reached the cap')]
        public bool $reached,
    ) {
    }
}
