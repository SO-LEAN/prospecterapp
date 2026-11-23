<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A file that the browser sent with an upload form (`POST /api/uploads`). The exchange keeps the file under its
 * original name.
 */
final readonly class AttachmentInput
{
    private const int LONGEST_NAME = 255;

    public function __construct(
        #[Assert\NotBlank]
        #[OA\Property(description: 'The upload that `UploadResource` names')]
        public string $upload = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: self::LONGEST_NAME)]
        public string $name = '',
    ) {
    }
}
