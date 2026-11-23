<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookSlot;

/**
 * Books a slot on a booking page. Anyone with the page's public link can book without signing in. The booker gives
 * their name and contact details, how they want to meet the host, and the subject. The client sends the appointment's
 * id. It also sends the id the booker gets when nobody in the account matches them.
 */
final readonly class BookSlot
{
    public function __construct(
        public string $code,
        public string $appointmentId,
        public string $bookerId,
        public string $channel,
        public \DateTimeImmutable $slot,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
        public ?string $subject,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
