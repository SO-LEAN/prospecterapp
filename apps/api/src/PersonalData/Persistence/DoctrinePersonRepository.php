<?php

namespace App\PersonalData\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrinePersonRepository implements PersonRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function nextIdentity(): PersonId
    {
        return new PersonId(Uuid::v7()->toRfc4122());
    }

    public function get(PersonId $id): Person
    {
        return $this->entityManager->find(Person::class, $id->value) ?? throw new UnknownPerson($id);
    }

    /**
     * @param list<PersonId> $ids
     *
     * @return list<Person>
     */
    public function ofIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->entityManager->createQuery(sprintf('SELECT p FROM %s p WHERE p.id IN (:ids)', Person::class))
            ->setParameter('ids', array_map(static fn (PersonId $id): string => $id->value, $ids))
            ->getResult();
    }

    public function save(Person $person): void
    {
        $this->ensureTheEmailIsFree($person);
        $this->entityManager->persist($person);
        $this->entityManager->flush();
    }

    /**
     * Two persons kept by the same account for the same purpose cannot share an email; other accounts may.
     */
    private function ensureTheEmailIsFree(Person $person): void
    {
        $email = $person->contactDetails()->email;

        if (null === $email) {
            return;
        }

        $taken = (int) $this->entityManager->createQuery(sprintf('SELECT COUNT(p.id) FROM %s p WHERE p.accountId = :account AND p.purpose = :purpose AND p.contactDetails.email = :email AND p.id != :id', Person::class))
            ->setParameter('account', $person->accountId(), 'account_id')
            ->setParameter('purpose', $person->purpose()->value)
            ->setParameter('email', $email, 'email')
            ->setParameter('id', $person->id(), 'person_id')
            ->getSingleScalarResult();

        if ($taken > 0) {
            throw new EmailAlreadyUsed();
        }
    }
}
