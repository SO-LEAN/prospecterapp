<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Solean\CleanProspecter\Entity\User;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\UserGateway;

/**
 * Class UserGatewayAdapter.
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
     * @param $id
     *
     * @return User
     */
    public function getUser($id): User
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['id' => $id]);

        if (null === $user) {
            throw new NotFoundException();
        }

        return $user;
    }

    /**
     * @param User $user
     *
     * @return User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function createUser(User $user): User
    {
        $this->entityManager->persist($user);
        $this->flushIfNeeded();

        return $user;
    }

    /**
     * @param $id
     * @param User $user
     *
     * @return User
     */
    public function saveUser($id, User $user): User
    {
        $user->setId($id);
        $this->entityManager->persist($user);
        $this->flushIfNeeded();

        return $user;
    }

    /**
     * @param array $criteria
     *
     * @return null|User
     */
    public function findOneBy(array $criteria): ?User
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository->findOneBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }

    private function flushIfNeeded(): void
    {
        if ($this->isTransactionActive()) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return bool
     */
    private function isTransactionActive(): bool
    {
        return $this->entityManager->getConnection()->isTransactionActive();
    }
}
