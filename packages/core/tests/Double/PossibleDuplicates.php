<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations an organization is suspected of being a duplicate of, by their name, as it keeps them: one no
 * longer prospected named by its identity, so that a suspicion the core forgot to lift shows.
 */
final class PossibleDuplicates
{
    /**
     * @return list<PossibleDuplicate>
     */
    public static function of(Organization $organization, InMemoryDatabase $database): array
    {
        return array_map(static fn (OrganizationId $id): PossibleDuplicate => new PossibleDuplicate($id->value, $database->organization((string) $id)?->fullName() ?? $id->value), StateOf::the($organization)->value('possibleDuplicates'));
    }
}
