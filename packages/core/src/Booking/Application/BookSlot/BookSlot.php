<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookSlot;

/**
 * Books a slot of the page a public link leads to, by anyone holding it, without signing in: who they are, how they
 * meet the host, and what about. The client gives the identity of the appointment and the one the booker gets
 * should nobody of the account be them.
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
