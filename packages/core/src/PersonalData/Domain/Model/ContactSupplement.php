<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidTitle;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\InvalidEmail;
use Prospecter\Core\SharedKernel\Domain\Contact\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\Link\NotAWebAddress;
use Prospecter\Core\SharedKernel\Domain\Link\ReferenceOfAnotherKind;
use Prospecter\Core\SharedKernel\Domain\Link\UnrecognizedPage;

/**
 * What a client knows about a contact, to fill what the contact lacks (ADR 0177). Each part is optional. The links may
 * go beyond the five links a person holds.
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
