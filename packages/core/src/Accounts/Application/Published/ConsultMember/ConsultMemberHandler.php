<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Published\ConsultMember;

use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultMemberHandler implements QueryHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(ConsultMember $query): MemberResponse
    {
        return MemberResponse::of($this->members->get(new MemberId($query->memberId)));
    }
}
