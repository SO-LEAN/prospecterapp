<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * The published language of the organizations that name a contact (ADR 0045). It gives their ids.
 */
final readonly class OrganizationsNamingContactResponse
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $personId,
        public array $organizationIds,
    ) {
    }
}
