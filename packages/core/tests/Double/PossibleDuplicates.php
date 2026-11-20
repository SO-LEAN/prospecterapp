<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations an organization is suspected of being a duplicate of, as the adapters read them: those still
 * prospected, by their name.
 */
final class PossibleDuplicates
{
    /**
     * @return list<PossibleDuplicate>
     */
    public static function of(Organization $organization, InMemoryDatabase $database): array
    {
        $duplicates = array_filter(array_map(static fn (OrganizationId $id): ?Organization => $database->organization((string) $id), StateOf::the($organization)['possibleDuplicates']));

        return array_values(array_map(static fn (Organization $duplicate): PossibleDuplicate => new PossibleDuplicate($duplicate->id()->value, $duplicate->fullName()), $duplicates));
    }
}
