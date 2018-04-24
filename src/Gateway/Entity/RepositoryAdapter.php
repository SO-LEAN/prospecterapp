<?php

namespace App\Gateway\Entity;

use stdClass;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Solean\CleanProspecter\Exception\Gateway as GatewayException;

abstract class RepositoryAdapter
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager, $entityClass)
    {
        $this->repository = $entityManager->getRepository($entityClass);
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     *
     * @return stdClass
     */
    protected function save($entity): object
    {
        try {
            $this->entityManager->persist($entity);
            $this->flushIfNeeded();
        } catch (UniqueConstraintViolationException $e) {
            throw new GatewayException\UniqueConstraintViolationException($e->getMessage(), $e->getCode(), $e);
        }

        return $entity;
    }

    protected function flushIfNeeded(): void
    {
        if (!$this->isTransactionActive()) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return bool
     */
    protected function isTransactionActive(): bool
    {
        return $this->entityManager->getConnection()->isTransactionActive();
    }
}