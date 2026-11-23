<?php

declare(strict_types=1);

namespace App\Booking\Input;

use App\Booking\Resource\WindowResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All my weekly windows at once. They replace the former ones. The time zone is my clock's, on which the windows are
 * set.
 */
final readonly class WindowsInput
{
    /**
     * @param list<array{weekday: string, from: string, to: string}> $windows
     */
    public function __construct(
        #[Assert\NotBlank]
        #[OA\Property(description: 'My clock\'s time zone, by its name in the time zone database', example: 'Europe/Paris')]
        public ?string $timeZone = null,
        #[Assert\NotNull]
        #[Assert\All([new Assert\Collection(fields: [
            'weekday' => [new Assert\NotBlank(), new Assert\Type('string')],
            'from' => [new Assert\NotBlank(), new Assert\Type('string')],
            'to' => [new Assert\NotBlank(), new Assert\Type('string')],
        ])])]
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: WindowResource::class)))]
        public ?array $windows = null,
    ) {
    }
}
