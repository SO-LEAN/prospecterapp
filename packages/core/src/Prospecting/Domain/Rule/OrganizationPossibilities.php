<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Rule;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;

/**
 * What an organization's state offers now (ADR 0088). The organization refuses the rest, and the organization's reading
 * tells it.
 */
final class OrganizationPossibilities
{
    /**
     * @return list<OrganizationIntent>
     */
    #[\NoDiscard]
    public static function given(bool $hasLogo, bool $hasHolding, bool $hasContacts, bool $hasPossibleDuplicates): array
    {
        return array_values(array_filter(OrganizationIntent::cases(), static fn (OrganizationIntent $intent): bool => match ($intent) {
            OrganizationIntent::RemoveLogo => $hasLogo,
            OrganizationIntent::DetachFromHolding => $hasHolding,
            OrganizationIntent::RemoveContact => $hasContacts,
            OrganizationIntent::DismissPossibleDuplicate => $hasPossibleDuplicates,
            default => true,
        }));
    }
}
