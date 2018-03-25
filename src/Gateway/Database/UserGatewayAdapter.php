<?php

namespace App\Gateway\Database;


use Doctrine\ORM\EntityManagerInterface;
use Solean\CleanProspecter\Entity\User;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Database\UserGateway;

/**
 * Class UserGatewayAdapter
 * @package App\Gateway\Database
 */
class UserGatewayAdapter implements UserGateway
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(User::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function commit(): void
    {
        $this->entityManager->flush();
    }

    /**
     * @param $id
     * @return User
     */
    public function getUser($id): User
    {
        $user = $this->repository->findOneBy(['id' => $id]);

        if (null === $user) {
            throw new NotFoundException();
        }

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     */
    public function createUser(User $user): User
    {
        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     */
    public function saveUser(User $user): User
    {
        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * @param array $criteria
     * @return null|User
     */
    public function findOneBy(array $criteria): ?User
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }
}