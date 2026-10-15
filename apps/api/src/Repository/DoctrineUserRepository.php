<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Domain\User\UnknownUser;
use Prospecter\Core\Domain\User\User;
use Prospecter\Core\Domain\User\UserId;
use Prospecter\Core\Domain\User\UserRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UniqueEmails $uniqueEmails,
    ) {
    }

    public function nextIdentity(): UserId
    {
        return new UserId(Uuid::v7()->toRfc4122());
    }

    public function get(UserId $id): User
    {
        return $this->entityManager->find(User::class, $id->value) ?? throw new UnknownUser($id);
    }

    public function ofUserName(string $userName): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['userName' => $userName]);
    }

    public function save(User $user): void
    {
        $this->uniqueEmails->assertFree($user->contactDetails()->email, $user->id());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
