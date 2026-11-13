<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationToDos;

/**
 * The to-dos kept for an organization, for it or for its contacts, those to do then those done.
 */
final readonly class ConsultOrganizationToDos
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
