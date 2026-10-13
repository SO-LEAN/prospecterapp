<?php

namespace App\Gateway\Entity;

use App\Service\PaginatorFactory;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Solean\CleanProspecter\Exception\Gateway as GatewayException;

abstract class RepositoryAdapter
{
    /**
     * @var EntityRepository<object>
     */
    protected readonly EntityRepository $repository;

    /**
     * @param class-string $entityClass
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        string $entityClass,
        protected readonly PaginatorFactory $paginatorFactory,
    ) {
        $this->repository = $entityManager->getRepository($entityClass);
    }

    protected function save(object $entity): object
    {
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new GatewayException\UniqueConstraintViolationException($e->getMessage(), $e->getCode(), $e);
        }

        return $entity;
    }
}
