<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * The service Accounts offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is absent.
 */
interface ConsultMembersService
{
    public function __invoke(ConsultMembers $query, ConsultMembersOutput $output): void;
}
