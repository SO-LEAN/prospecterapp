<?php

namespace App\PersonalData\Persistence;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class DoctrinePersonRepository implements PersonRepository
{
    public function __construct(
        private EntityManagerInterface $personalDataEntityManager,
    ) {
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

    public function sliceOf(AccountId $accountId, Purpose $purpose, SearchTerms $search, ?string $after, int $max): Slice
    {
        $query = $this->personalDataEntityManager->createQueryBuilder()->select('p')->from(Person::class, 'p')
            ->where('p.accountId = :account AND p.purpose = :purpose')
            ->setParameter('account', $accountId, 'account_id')
            ->setParameter('purpose', $purpose->value)
            ->orderBy('p.id', 'DESC');

        if (null !== $after) {
            $query->andWhere('p.id < :after')->setParameter('after', $after);
        }

        $found = array_values(array_filter($query->getQuery()->getResult(), static fn (Person $person): bool => $person->searchTerms()->findBy($search)));
        $slice = array_slice($found, 0, $max);

        return new Slice($slice, count($found) > $max ? (string) array_last($slice)->id() : null);
    }

    public function remove(Person $person): void
    {
        $this->personalDataEntityManager->remove($person);
        $this->personalDataEntityManager->flush();
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        return (int) $this->personalDataEntityManager->createQuery(sprintf('SELECT COUNT(p.id) FROM %s p WHERE p.accountId = :account AND p.purpose = :purpose AND p.contactDetails.email = :email AND p.id != :except', Person::class))
            ->setParameter('account', $accountId, 'account_id')
            ->setParameter('purpose', $purpose->value)
            ->setParameter('email', $email, 'email')
            ->setParameter('except', $except, 'person_id')
            ->getSingleScalarResult() > 0;
    }

    public function save(Person $person): void
    {
        $this->personalDataEntityManager->persist($person);

        try {
            $this->personalDataEntityManager->flush();
        } catch (UniqueConstraintViolationException $takenMeanwhile) {
            throw EmailAlreadyUsed::byAnother($takenMeanwhile);
        }
    }
}
