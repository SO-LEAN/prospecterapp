<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\IncompleteBooker;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidEmail;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Who books a slot, as they say it: the host meets them by phone or by the invitation sent to their e-mail.
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
}
