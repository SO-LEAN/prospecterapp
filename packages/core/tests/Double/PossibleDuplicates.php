<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations that an organization may duplicate, by their names, as the organization keeps them. An
 * organization no longer prospected is named by its id, so that a suspicion the core forgot to lift shows.
 */
final class PossibleDuplicates
{
    /**
     * @return list<PossibleDuplicate>
     */
    public static function of(Organization $organization, InMemoryDatabase $database): array
    {
        return array_map(static fn (OrganizationId $id): PossibleDuplicate => new PossibleDuplicate($id->value, $database->organization((string) $id)?->fullName() ?? $id->value), StateOf::the($organization)->listOf('possibleDuplicates', OrganizationId::class));
    }
}
