<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidWebsite;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidEmail;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidSiret;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAWebAddress;
use Prospecter\Core\SharedKernel\Domain\Exception\ReferenceOfAnotherKind;
use Prospecter\Core\SharedKernel\Domain\Exception\UnrecognizedPage;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * What a client knows of an organization, to fill what it lacks (ADR 0177): each part optional, links beyond the
 * five an organization holds included.
 */
final readonly class OrganizationSupplement
{
    /**
     * @param list<ExternalReference> $links
     */
    public function __construct(
        public ?Siret $siret = null,
        public array $links = [],
        public ?Website $website = null,
        public ?Email $email = null,
        public ?PhoneNumber $phoneNumber = null,
    ) {
    }

    /**
     * @param list<string> $links the addresses of its links
     *
     * @throws InvalidSiret|NotAWebAddress|ReferenceOfAnotherKind|UnrecognizedPage|InvalidWebsite|InvalidEmail|InvalidPhoneNumber
     */
    // @phpstan-ignore prospecter.parameters (a value object listing its fields, as its constructor does)
    public static function given(?string $siret, array $links, ?string $website, ?string $email, ?string $phoneNumber): self
    {
        return new self(
            Siret::orNone($siret),
            array_map(static fn (string $address): ExternalReference => ExternalReference::designating($address, Designated::Organization), $links),
            Website::orNone($website),
            Email::orNone($email),
            PhoneNumber::orNone($phoneNumber),
        );
    }
}
