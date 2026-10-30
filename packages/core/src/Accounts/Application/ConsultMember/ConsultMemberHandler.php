<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMember;

use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMemberOutput;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMemberHandler implements QueryHandler
{
    public function __construct(private MemberRetriever $memberRetriever)
    {
    }

    public function __invoke(ConsultMember $query, ConsultMemberOutput $output): void
    {
        $output->present($this->memberRetriever->get(new MemberId($query->memberId)));
    }
}
