<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultOrganizationToDos;

/**
 * Reads the to-dos kept for an organization, for the organization itself or for its contacts. The to-dos still to do
 * come first, then those done.
 */
final readonly class ConsultOrganizationToDos
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
