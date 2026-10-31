<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMembers;

use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersOutput;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMembersHandler implements QueryHandler, ConsultMembersService
{
    public function __construct(private MemberRetriever $memberRetriever)
    {
    }

    public function __invoke(ConsultMembers $query, ConsultMembersOutput $output): void
    {
        $output->present($this->memberRetriever->ofIds(array_map(static fn (string $id): MemberId => new MemberId($id), $query->memberIds)));
    }
}
