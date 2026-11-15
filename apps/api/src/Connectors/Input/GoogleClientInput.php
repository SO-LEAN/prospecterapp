<?php

namespace App\Connectors\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The client an administrator made for their account in Google Cloud: its id and its secret.
 */
final readonly class GoogleClientInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $clientId = null,
        #[Assert\NotBlank]
        #[\SensitiveParameter]
        public ?string $clientSecret = null,
    ) {
    }
}
