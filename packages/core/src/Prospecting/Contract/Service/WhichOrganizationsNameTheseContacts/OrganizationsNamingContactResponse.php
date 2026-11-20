<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * The published language of the organizations naming a contact (ADR 0045): their identities.
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
