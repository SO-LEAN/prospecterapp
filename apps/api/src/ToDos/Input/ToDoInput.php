<?php

namespace App\ToDos\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A to-do noted or revised, shaped like `ToDoResource`.
 */
final readonly class ToDoInput
{
    public const int MAX_TEXT = 500;

    public function __construct(
        #[Assert\NotNull]
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        #[OA\Property(description: 'The day it is due, `Y-m-d`', format: 'date')]
        public ?string $dueOn = null,
        #[Assert\Regex('/^([01]\d|2[0-3]):[0-5]\d$/', message: 'This value is not a valid time.')]
        #[OA\Property(description: 'The hour of that day, `H:i`, when said', example: '08:30')]
        public ?string $dueAt = null,
    ) {
    }
}
