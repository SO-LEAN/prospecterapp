<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * How a person presents themselves, as PersonalData keeps it: their name and their picture, each one when known.
 */
final readonly class HostPresentation
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $pictureUrl,
    ) {
    }
}
