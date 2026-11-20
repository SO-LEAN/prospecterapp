<?php

namespace App\Shared\Mcp;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The address on the web of an image an AI gives, a logo or a picture, in place of the file the screen sends
 * (ADR 0168).
 */
final readonly class ImageAddressInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: true)]
        #[Assert\Length(max: 2048)]
        public string $url = '',
    ) {
    }
}
