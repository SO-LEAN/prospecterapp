<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Gateway\Entity\Transaction;

class TransactionEntityManagerAdapter implements Transaction
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
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
