<?php

declare(strict_types=1);

namespace App\Booking\Input;

use App\Booking\Resource\WindowResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The windows of my week, all at once, replacing the former ones, and the time zone of my clock they are set on.
 */
final readonly class WindowsInput
{
    /**
     * @param list<array{weekday: string, from: string, to: string}> $windows
     */
    public function __construct(
        #[Assert\NotBlank]
        #[OA\Property(description: 'The time zone of my clock, by its name in the database of the time zones', example: 'Europe/Paris')]
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
