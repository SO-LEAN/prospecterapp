<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

/**
 * The organization proposed to attach a contact to.
 */
final readonly class ProposedOrganization
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
