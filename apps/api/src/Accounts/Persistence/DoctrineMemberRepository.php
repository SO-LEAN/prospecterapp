<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\UniqueEmails;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineMemberRepository implements MemberRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UniqueEmails $uniqueEmails,
    ) {
    }

    public function nextIdentity(): MemberId
    {
        return new MemberId(Uuid::v7()->toRfc4122());
    }

    public function get(MemberId $id): Member
    {
        return $this->entityManager->find(Member::class, $id->value) ?? throw new UnknownMember($id);
    }

    public function ofUserName(string $userName): ?Member
    {
        return $this->entityManager->getRepository(Member::class)->findOneBy(['userName' => $userName]);
    }

    public function save(Member $member): void
    {
        $this->uniqueEmails->assertFree($member->contactDetails()->email, $member->id());
        $this->entityManager->persist($member);
        $this->entityManager->flush();
    }
}
