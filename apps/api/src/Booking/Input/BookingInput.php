<?php

namespace App\Booking\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A slot booked by its public link: who books it and how they meet the host; the booking page refuses whatever
 * it does not offer. `website` is a trap no person fills, `formShownAt` when the page showed the form: a form filled
 * by a robot books nothing, without saying so (ADR 0164).
 */
final readonly class BookingInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DATE_ATOM)]
        #[OA\Property(description: 'The start of a free slot, as the page offers it', example: '2026-11-24T09:00:00+01:00')]
        public ?string $slot = null,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['call', 'videoCall'])]
        public ?string $channel = null,
        #[OA\Property(example: 'Ada')]
        public string $firstName = '',
        #[OA\Property(example: 'Lovelace')]
        public string $lastName = '',
        #[OA\Property(description: 'As typed, French without its country code', example: '06 12 34 56 78')]
        public string $phone = '',
        #[OA\Property(example: 'ada@acme.fr')]
        public string $email = '',
        #[Assert\Length(max: 1000)]
        #[OA\Property(description: 'What the booker wants to talk about, may be left out', example: 'Mission data en mars')]
        public ?string $subject = null,
        #[OA\Property(description: 'Left empty by a person: the field is hidden')]
        public ?string $website = null,
        #[Assert\DateTime(format: \DATE_ATOM)]
        #[OA\Property(description: 'When the page showed the form', example: '2026-11-23T10:05:00+01:00')]
        public ?string $formShownAt = null,
    ) {
    }
}
