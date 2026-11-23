<?php

declare(strict_types=1);

namespace App\Booking\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A slot booked through a public link: who books it and how they meet the host. The booking page refuses what it does
 * not offer. `website` is a trap that no person fills. `formShownAt` is when the page showed the form. A form that a
 * robot filled books nothing, and the API does not say so (ADR 0164).
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
        #[OA\Property(description: 'As typed. A French number may leave out its country code', example: '06 12 34 56 78')]
        public string $phone = '',
        #[OA\Property(example: 'ada@acme.fr')]
        public string $email = '',
        #[Assert\Length(max: 1000)]
        #[OA\Property(description: 'What the booker wants to talk about. Optional', example: 'Mission data en mars')]
        public ?string $subject = null,
        #[OA\Property(description: 'Left empty by a person: the field is hidden')]
        public ?string $website = null,
        #[OA\Property(description: 'When the page showed the form', example: '2026-11-23T09:05:00.000Z')]
        public ?\DateTimeImmutable $formShownAt = null,
    ) {
    }
}
