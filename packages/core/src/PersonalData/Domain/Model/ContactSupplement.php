<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidTitle;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidEmail;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAWebAddress;
use Prospecter\Core\SharedKernel\Domain\Exception\ReferenceOfAnotherKind;
use Prospecter\Core\SharedKernel\Domain\Exception\UnrecognizedPage;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * What a client knows of a contact, to fill what they lack (ADR 0177): each part optional, links beyond the five a
 * person holds included.
 */
final readonly class ContactSupplement
{
    /**
     * @param list<ExternalReference> $links
     */
    public function __construct(
        public Civility $civility = Civility::X,
        public ?string $jobTitle = null,
        public ?Email $email = null,
        public ?PhoneNumber $phoneNumber = null,
        public array $links = [],
    ) {
    }

    /**
     * @param list<string> $links the addresses of their links
     *
     * @throws InvalidTitle|InvalidEmail|InvalidPhoneNumber|NotAWebAddress|ReferenceOfAnotherKind|UnrecognizedPage
     */
    // @phpstan-ignore prospecter.parameters (a value object listing its fields, as its constructor does)
    public static function given(string $civility, ?string $jobTitle, ?string $email, ?string $phoneNumber, array $links): self
    {
        return new self(
            Civility::of($civility),
            $jobTitle,
            Email::orNone($email),
            PhoneNumber::orNone($phoneNumber),
            array_map(static fn (string $address): ExternalReference => ExternalReference::designating($address, Designated::Person), $links),
        );
    }
}
