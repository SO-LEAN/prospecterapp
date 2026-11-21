<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;
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
}
