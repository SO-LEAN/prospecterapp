<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ConsultMember;

use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMemberOutput;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMemberHandler implements QueryHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(ConsultMember $query, ConsultMemberOutput $output): void
    {
        $output->present($this->published($this->members->get(new MemberId($query->memberId))));
    }

    private function published(Member $member): MemberResponse
    {
        return new MemberResponse((string) $member->id(), (string) $member->accountId(), (string) $member->personId(), $member->readsPersonalData());
    }
}
