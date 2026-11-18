<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089, 0153): it checks no caller and refuses nothing,
 * a person unknown, forgotten or prospected by the account is absent from its answer.
 */
interface PresentMembersService
{
    public function __invoke(PresentMembers $query, PresentMembersOutput $output): void;
}
