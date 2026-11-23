<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** Why a recipient is dismissed without an exchange, when the member gives a reason. */
final readonly class DismissalInput
{
    private const int MAX_REASON = 500;

    public function __construct(
        #[OA\Property(description: 'The reason, when given')]
        #[Assert\Length(max: self::MAX_REASON)]
        public ?string $reason = null,
    ) {
    }
}
