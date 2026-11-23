<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange's corrected text, with the same shape as in `ExchangeDetailResource`. Its other facts stay the same.
 */
final readonly class TextCorrectionInput
{
    public function __construct(
        #[Assert\Length(max: CorrectionInput::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Declares the exchange redacted once its contact is forgotten. Allowed only while the `redact` link is offered')]
        public bool $redacted = false,
    ) {
    }
}
