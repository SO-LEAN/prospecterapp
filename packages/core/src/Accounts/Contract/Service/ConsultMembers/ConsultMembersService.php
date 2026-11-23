<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * The service that Accounts offers the other contexts (ADR 0045, 0089). It refuses nothing. An unknown member is
 * absent from its answer.
 */
interface ConsultMembersService
{
    public function __invoke(ConsultMembers $query, ConsultMembersOutput $output): void;
}
