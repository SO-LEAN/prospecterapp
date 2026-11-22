<?php

declare(strict_types=1);

namespace App\Connectors\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * What Google gave the SPA when it brought the member back: the code of authorization and the state of the request.
 */
final readonly class GoogleAnswerInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $code = null,
        #[Assert\NotBlank]
        public ?string $state = null,
    ) {
    }
}
