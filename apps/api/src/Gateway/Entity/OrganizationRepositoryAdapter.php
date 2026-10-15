<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\Query;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Entity\Page;
use Prospecter\Core\Gateway\Entity\PageRequest;

class OrganizationRepositoryAdapter extends RepositoryAdapter implements OrganizationGateway
{
    public function get(int $id): Organization
    {
        return $this->findOneBy(['id' => $id]) ?? throw new NotFoundException();
    }

    public function create(Organization $organization): Organization
    {
        $this->save($organization);

        return $organization;
    }

    public function update(int $id, Organization $organization): Organization
    {
        $organization->setId($id);
        $this->save($organization);

        return $organization;
    }

    public function findOneBy(array $criteria): ?Organization
    {
        return $this->repository->findOneBy($criteria);
    }

    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }

    public function findPageByQuery(PageRequest $pageRequest): Page
    {
        $query = $this->entityManager->createQuery($this->buildFindPageByQueryDql($pageRequest))
            ->setParameter('query', sprintf('%%%s%%', $pageRequest->query))
            ->setFirstResult(($pageRequest->page - 1) * $pageRequest->maxByPage)
            ->setMaxResults($pageRequest->maxByPage);

        $this->applyFilterParameters($pageRequest, $query);

        $paginator = $this->paginatorFactory->create($query);
        $total = $paginator->count();

        return new Page($pageRequest->page, $total, intdiv($total - 1, $pageRequest->maxByPage) + 1, iterator_to_array($paginator, false));
    }

    private function buildFindPageByQueryDql(PageRequest $pageRequest): string
    {
        $where = '(o.corporateName LIKE :query OR o.email LIKE :query)';

        foreach (array_keys($pageRequest->filter) as $field) {
            $where .= sprintf(' AND o.%s = :%s', $field, $field);
        }

        return sprintf('SELECT o FROM %s o WHERE %s ORDER BY o.id DESC', Organization::class, $where);
    }

    private function applyFilterParameters(PageRequest $pageRequest, Query $query): void
    {
        foreach ($pageRequest->filter as $field => $value) {
            $query->setParameter($field, $value);
        }
    }
}
