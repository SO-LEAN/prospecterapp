<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationIntent;

/**
 * What the state of an organization offers now (ADR 0088): the organization refuses the rest, the reading of an
 * organization tells it.
 */
final class OrganizationPossibilities
{
    /**
     * @return list<OrganizationIntent>
     */
    public static function given(bool $hasLogo, bool $hasHolding, bool $hasContacts): array
    {
        return array_values(array_filter(OrganizationIntent::cases(), static fn (OrganizationIntent $intent): bool => match ($intent) {
            OrganizationIntent::RemoveLogo => $hasLogo,
            OrganizationIntent::DetachFromHolding => $hasHolding,
            OrganizationIntent::RemoveContact => $hasContacts,
            default => true,
        }));
    }
}
