<?php

namespace App\Prospecting;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Whether the signed-in member reaches an organization: one of their account (ADR 0081).
 *
 * @extends AccountResourceVoter<OrganizationSheet>
 */
final class OrganizationVoter extends AccountResourceVoter
{
    public const string ACCESS = 'ORGANIZATION_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof OrganizationSheet;
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownOrganization';
    }
}
