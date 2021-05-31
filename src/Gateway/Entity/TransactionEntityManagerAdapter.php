<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Solean\CleanProspecter\Gateway\Entity\Transaction;

/**
 * Class TransactionEntityManagerAdapter.
 */
class TransactionEntityManagerAdapter implements Transaction
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function begin(): void
    {
        $this->entityManager->beginTransaction();
    }

    public function commit(): void
    {
        $this->entityManager->commit();
    }

    public function rollback(): void
    {
        $this->entityManager->rollback();
    }
}
