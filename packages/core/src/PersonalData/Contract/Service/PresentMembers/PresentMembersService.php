<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089, 0153). It checks no caller and refuses nothing.
 * Its answer leaves out a person who is unknown, forgotten or prospected by the account.
 */
interface PresentMembersService
{
    public function __invoke(PresentMembers $query, PresentMembersOutput $output): void;
}
