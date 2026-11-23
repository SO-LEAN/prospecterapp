<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidWebsite;
use Prospecter\Core\SharedKernel\Domain\Company\InvalidSiret;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
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
 * What a client knows about an organization, to fill what it lacks (ADR 0177). Each part is optional. It may hold
 * more links than the five an organization holds.
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
