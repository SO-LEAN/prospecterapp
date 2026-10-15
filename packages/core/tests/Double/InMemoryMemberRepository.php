<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class InMemoryMemberRepository implements MemberRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function nextIdentity(): MemberId
    {
        return new MemberId($this->database->uuids->next());
    }

    public function get(MemberId $id): Member
    {
        return $this->database->member((string) $id) ?? throw new UnknownMember($id);
    }

    public function ofUserName(string $userName): ?Member
    {
        foreach ($this->database->members() as $member) {
            if ($member->userName() === $userName) {
                return $member;
            }
        }

        return null;
    }

    public function save(Member $member): void
    {
        $this->database->storeMember($member);
    }
}
