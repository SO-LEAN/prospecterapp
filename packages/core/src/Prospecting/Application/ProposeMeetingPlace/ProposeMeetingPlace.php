<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

/**
 * Proposes where to meet a contact while a member plans a meeting with them: the address of their organization.
 */
final readonly class ProposeMeetingPlace
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
