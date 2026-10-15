<?php

namespace App\Repository;

use App\Service\PaginatorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\Organization\UnknownOrganization;
use Prospecter\Core\Domain\Shared\Page;
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
        $this->uniqueEmails->assertFree($organization->contactDetails()->email, $organization->id()->value);
        $this->entityManager->persist($organization);
        $this->entityManager->flush();
    }

    public function countProspectedBy(OrganizationId $ownerId): int
    {
        return (int) $this->entityManager->createQuery(sprintf('SELECT COUNT(o.id) FROM %s o WHERE o.ownerId = :owner', Organization::class))
            ->setParameter('owner', $ownerId->value)
            ->getSingleScalarResult();
    }

    public function pageProspectedBy(OrganizationId $ownerId, string $query, int $page, int $maxByPage): Page
    {
        $dql = sprintf('SELECT o FROM %s o WHERE o.ownerId = :owner AND (o.corporateName LIKE :query OR o.email LIKE :query) ORDER BY o.id DESC', Organization::class);
        $paginator = $this->paginatorFactory->create($this->entityManager->createQuery($dql)
            ->setParameters(['owner' => $ownerId->value, 'query' => sprintf('%%%s%%', $query)])
            ->setFirstResult(($page - 1) * $maxByPage)
            ->setMaxResults($maxByPage));
        $total = $paginator->count();

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, iterator_to_array($paginator, false));
    }
}
