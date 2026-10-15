<?php

namespace App\Prospecting\Persistence;

use App\Shared\PaginatorFactory;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Page;

final readonly class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private EntityManagerInterface $prospectingEntityManager,
        private PaginatorFactory $paginatorFactory,
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

    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page
    {
        $dql = sprintf('SELECT o FROM %s o WHERE o.accountId = :account AND (o.legalIdentity.corporateName LIKE :query OR o.contactDetails.email LIKE :query) ORDER BY o.id DESC', Organization::class);
        $paginator = $this->paginatorFactory->create($this->prospectingEntityManager->createQuery($dql)
            ->setParameter('account', $accountId, 'account_id')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->setFirstResult(($page - 1) * $maxByPage)
            ->setMaxResults($maxByPage));
        $total = $paginator->count();

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, iterator_to_array($paginator, false));
    }
}
