<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\IncompleteBooker;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\InvalidEmail;
use Prospecter\Core\SharedKernel\Domain\Contact\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;

/**
 * The person who books a slot, as they describe themselves. The host meets them by phone, or through the invitation
 * sent to their e-mail.
 */
final readonly class Booker
{
    private function __construct(
        public string $firstName,
        public string $lastName,
        public PhoneNumber $phone,
        public Email $email,
    ) {
    }

    /**
     * @throws IncompleteBooker|InvalidPhoneNumber|InvalidEmail
     */
    public static function named(string $firstName, string $lastName, string $phone, string $email): self
    {
        if (array_any([$firstName, $lastName, $phone, $email], static fn (string $given): bool => '' === trim($given))) {
            throw IncompleteBooker::refused();
        }

        return new self(trim($firstName), trim($lastName), PhoneNumber::of($phone), new Email(trim($email)));
    }

    public function hasEmail(Email $email): bool
    {
        return $this->email->equals($email);
    }
}
