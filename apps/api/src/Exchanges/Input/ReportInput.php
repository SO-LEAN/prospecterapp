<?php

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What a planned exchange became once it took place, shaped like the moment and the text of
 * `ExchangeDetailResource`.
 */
final readonly class ReportInput
{
    public function __construct(
        #[OA\Property(format: 'date-time', description: 'When it really took place, its planned moment when left out; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: CorrectionInput::MAX_TEXT)]
        #[OA\Property(description: 'Its report, which replaces its agenda')]
        public ?string $text = null,
    ) {
    }
}
