<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The address on the web of a file an AI gives, an image or an attachment, in place of the file the screen sends
 * (ADR 0168).
 */
final readonly class WebAddressInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: true)]
        #[Assert\Length(max: 2048)]
        public string $url = '',
    ) {
    }
}
