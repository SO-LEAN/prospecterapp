<?php

namespace App\Shared\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * An email belongs to one account, one member or one prospected organization: the rule spans three tables
 * and two contexts, a unique index cannot hold it. Inherited from the former single table of persons, it
 * goes with the personal data context (step 2 of ADR 0030).
 */
final readonly class UniqueEmails
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function assertFree(?Email $email, Uuid $ownerId): void
    {
        if (null !== $email && ($this->isUsedBy(Account::class, $email, $ownerId) || $this->isUsedBy(Member::class, $email, $ownerId) || $this->isUsedBy(Organization::class, $email, $ownerId))) {
            throw new EmailAlreadyUsed();
        }
    }

    /**
     * @param class-string $aggregate
     */
    private function isUsedBy(string $aggregate, Email $email, Uuid $ownerId): bool
    {
        return (int) $this->entityManager->createQuery(sprintf('SELECT COUNT(a.id) FROM %s a WHERE a.contactDetails.email = :email AND a.id != :id', $aggregate))
            ->setParameters(['email' => $email->value, 'id' => $ownerId->value])
            ->getSingleScalarResult() > 0;
    }
}
