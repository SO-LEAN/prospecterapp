<?php

declare(strict_types=1);

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TaggedContactsQuery
{
    private const string SEPARATOR = ',';

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        #[OA\Property(description: 'The tags chosen, 1 to 5, by their names without their mark, separated by commas', example: 'ESN,EMAIL')]
        public string $tags = '',
        #[Assert\Length(max: 200)]
        public ?string $after = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public function chosen(): array
    {
        return explode(self::SEPARATOR, $this->tags);
    }
}
