<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

/**
 * The address of a contact's organization, proposed as the place to meet them. The client writes it on one line and
 * names the country in its own language.
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
