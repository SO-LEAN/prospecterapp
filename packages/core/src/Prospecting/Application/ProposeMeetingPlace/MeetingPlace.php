<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

/**
 * The address of the organization of a contact, proposed as the place to meet them; written on one line by its
 * client, the country named in its language.
 */
final readonly class MeetingPlace
{
    public function __construct(
        public string $organizationId,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
    ) {
    }
}
