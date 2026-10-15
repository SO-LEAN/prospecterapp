<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\IdentifyMember;

use Prospecter\Core\Accounts\Application\MemberResponse;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class IdentifyMemberHandler implements QueryHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(IdentifyMember $query): MemberResponse
    {
        return MemberResponse::of($this->members->get(new MemberId($query->memberId)));
    }
}
