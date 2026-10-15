<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class InMemoryMemberRepository implements MemberRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(MemberId $id): Member
    {
        return $this->database->member((string) $id) ?? throw UnknownMember::identifiedBy($id);
    }

    public function save(Member $member): void
    {
        $this->database->storeMember($member);
    }
}
