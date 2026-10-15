<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\EmailAlreadyUsed;
use Prospecter\Core\Domain\User\User;

/**
 * An email belongs to one organization or one user: the rule spans two tables, a unique index cannot hold it.
 */
final readonly class UniqueEmails
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function assertFree(?Email $email, string $ownerId): void
    {
        if (null !== $email && ($this->isUsedBy(Organization::class, $email, $ownerId) || $this->isUsedBy(User::class, $email, $ownerId))) {
            throw new EmailAlreadyUsed();
        }
    }

    /**
     * @param class-string $aggregate
     */
    private function isUsedBy(string $aggregate, Email $email, string $ownerId): bool
    {
        return (int) $this->entityManager->createQuery(sprintf('SELECT COUNT(a.id) FROM %s a WHERE a.email = :email AND a.id != :id', $aggregate))
            ->setParameters(['email' => $email->value, 'id' => $ownerId])
            ->getSingleScalarResult() > 0;
    }
}
