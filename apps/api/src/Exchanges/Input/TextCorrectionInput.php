<?php

namespace App\Exchanges\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The corrected text of an exchange, shaped like the text of `ExchangeDetailResource`; its other facts stay.
 */
final readonly class TextCorrectionInput
{
    public function __construct(
        #[Assert\Length(max: CorrectionInput::MAX_TEXT)]
        public ?string $text = null,
    ) {
    }
}
