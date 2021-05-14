<?php

namespace App\Gateway\Entity;

use App\Service\PaginatorFactory;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Solean\CleanProspecter\Exception\Gateway as GatewayException;
use stdClass;

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

    /**
     * @var PaginatorFactory
     */
    protected $paginatorFactory;

    public function __construct(EntityManagerInterface $entityManager, $entityClass, PaginatorFactory $paginatorFactory)
    {
        $this->repository = $entityManager->getRepository($entityClass);
        $this->entityManager = $entityManager;
        $this->paginatorFactory = $paginatorFactory;
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
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new GatewayException\UniqueConstraintViolationException($e->getMessage(), $e->getCode(), $e);
        }

        return $entity;
    }
}
