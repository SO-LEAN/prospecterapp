<?php

namespace App\Prospecting\Persistence;

use App\Service\PaginatorFactory;
use App\Shared\Persistence\UniqueEmails;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Page;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UniqueEmails $uniqueEmails,
        private PaginatorFactory $paginatorFactory,
    ) {
    }

    public function nextIdentity(): OrganizationId
    {
        return new OrganizationId(Uuid::v7()->toRfc4122());
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->entityManager->find(Organization::class, $id->value) ?? throw new UnknownOrganization($id);
    }

    public function save(Organization $organization): void
    {
        $this->uniqueEmails->assertFree($organization->contactDetails()->email, $organization->id());
        $this->entityManager->persist($organization);
        $this->entityManager->flush();
    }

    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page
    {
        $dql = sprintf('SELECT o FROM %s o WHERE o.accountId = :account AND (o.legalIdentity.corporateName LIKE :query OR o.contactDetails.email LIKE :query) ORDER BY o.id DESC', Organization::class);
        $paginator = $this->paginatorFactory->create($this->entityManager->createQuery($dql)
            ->setParameter('account', $accountId, 'account_id')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->setFirstResult(($page - 1) * $maxByPage)
            ->setMaxResults($maxByPage));
        $total = $paginator->count();

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, iterator_to_array($paginator, false));
    }
}
