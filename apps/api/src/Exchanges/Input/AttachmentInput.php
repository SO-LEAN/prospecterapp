<?php

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A file the browser sent with the form of an upload (`POST /api/uploads`), joined under the name it had.
 */
final readonly class AttachmentInput
{
    private const int LONGEST_NAME = 255;

    public function __construct(
        #[Assert\NotBlank]
        #[OA\Property(description: 'The upload of `UploadResource`')]
        public string $upload = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: self::LONGEST_NAME)]
        public string $name = '',
    ) {
    }
}
