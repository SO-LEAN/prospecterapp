<?php

namespace App\Prospecting\Persistence;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private EntityManagerInterface $prospectingEntityManager,
    ) {
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->prospectingEntityManager->find(Organization::class, $id->value) ?? throw UnknownOrganization::identifiedBy($id);
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        return (int) $this->prospectingEntityManager->createQuery(sprintf('SELECT COUNT(o.id) FROM %s o WHERE o.accountId = :account AND o.contactDetails.email = :email AND o.id != :except', Organization::class))
            ->setParameter('account', $accountId, 'account_id')
            ->setParameter('email', $email, 'email')
            ->setParameter('except', $except, 'organization_id')
            ->getSingleScalarResult() > 0;
    }

    public function save(Organization $organization): void
    {
        $this->prospectingEntityManager->persist($organization);

        try {
            $this->prospectingEntityManager->flush();
        } catch (UniqueConstraintViolationException $takenMeanwhile) {
            throw EmailAlreadyUsed::byAnother($takenMeanwhile);
        }
    }

    public function remove(Organization $organization): void
    {
        $this->prospectingEntityManager->remove($organization);
        $this->prospectingEntityManager->flush();
    }

    public function subsidiariesOf(OrganizationId $holding): array
    {
        return $this->prospectingEntityManager->createQuery(sprintf('SELECT o FROM %s o WHERE o.holdingId = :holding', Organization::class))
            ->setParameter('holding', $holding, 'organization_id')
            ->getResult();
    }

    /**
     * The contacts are a JSON list of identities: an organization names a contact when the list holds its
     * quoted identity.
     */
    public function withContacts(array $contacts, AccountId $accountId): array
    {
        if ([] === $contacts) {
            return [];
        }

        $query = $this->prospectingEntityManager->createQueryBuilder()->select('o')->from(Organization::class, 'o')
            ->where('o.accountId = :account')
            ->setParameter('account', $accountId, 'account_id');
        $namesAContact = $query->expr()->orX();

        foreach ($contacts as $index => $contact) {
            $namesAContact->add(sprintf('o.contacts LIKE :contact%d', $index));
            $query->setParameter(sprintf('contact%d', $index), sprintf('%%"%s"%%', $contact->value), 'string');
        }

        return $query->andWhere($namesAContact)->getQuery()->getResult();
    }

    public function sliceProspectedBy(AccountId $accountId, SearchTerms $search, ?string $after, int $max): Slice
    {
        $query = $this->prospectingEntityManager->createQueryBuilder()->select('o')->from(Organization::class, 'o')
            ->where('o.accountId = :account')
            ->setParameter('account', $accountId, 'account_id')
            ->orderBy('o.id', 'DESC');

        if (null !== $after) {
            $query->andWhere('o.id < :after')->setParameter('after', $after);
        }

        $found = array_values(array_filter($query->getQuery()->getResult(), static fn (Organization $organization): bool => $organization->searchTerms()->findBy($search)));
        $slice = array_slice($found, 0, $max);

        return new Slice($slice, count($found) > $max ? (string) array_last($slice)->id() : null);
    }
}
