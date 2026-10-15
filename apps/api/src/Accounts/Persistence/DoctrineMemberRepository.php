<?php

namespace App\Accounts\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class DoctrineMemberRepository implements MemberRepository
{
    public function __construct(
        private EntityManagerInterface $accountsEntityManager,
    ) {
    }

    public function get(MemberId $id): Member
    {
        return $this->accountsEntityManager->find(Member::class, $id->value) ?? throw UnknownMember::identifiedBy($id);
    }

    public function save(Member $member): void
    {
        $this->accountsEntityManager->persist($member);
        $this->accountsEntityManager->flush();
    }
}
