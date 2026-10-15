<?php

namespace App\PersonalData\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class DoctrinePersonRepository implements PersonRepository
{
    public function __construct(private EntityManagerInterface $personalDataEntityManager)
    {
    }

    public function get(PersonId $id): Person
    {
        return $this->personalDataEntityManager->find(Person::class, $id->value) ?? throw UnknownPerson::identifiedBy($id);
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

        return $this->personalDataEntityManager->createQuery(sprintf('SELECT p FROM %s p WHERE p.id IN (:ids)', Person::class))
            ->setParameter('ids', array_map(static fn (PersonId $id): string => $id->value, $ids))
            ->getResult();
    }

    public function save(Person $person): void
    {
        $this->ensureTheEmailIsFree($person);
        $this->personalDataEntityManager->persist($person);
        $this->personalDataEntityManager->flush();
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

        $taken = (int) $this->personalDataEntityManager->createQuery(sprintf('SELECT COUNT(p.id) FROM %s p WHERE p.accountId = :account AND p.purpose = :purpose AND p.contactDetails.email = :email AND p.id != :id', Person::class))
            ->setParameter('account', $person->accountId(), 'account_id')
            ->setParameter('purpose', $person->purpose()->value)
            ->setParameter('email', $email, 'email')
            ->setParameter('id', $person->id(), 'person_id')
            ->getSingleScalarResult();

        if ($taken > 0) {
            throw EmailAlreadyUsed::byAnother();
        }
    }
}
