<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The corrected text of an exchange, shaped like the text of `ExchangeDetailResource`; its other facts stay.
 */
final readonly class TextCorrectionInput
{
    public function __construct(
        #[Assert\Length(max: CorrectionInput::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Declares the exchange redacted, its contact forgotten; only while the link redact is offered')]
        public bool $redacted = false,
    ) {
    }
}
