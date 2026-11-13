<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

/**
 * Where to meet a contact, proposed while planning a meeting with them: the address of their organization.
 */
final readonly class ProposeMeetingPlace
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
