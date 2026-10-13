<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\Query;
use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\OrganizationGateway;
use Solean\CleanProspecter\Gateway\Entity\Page;
use Solean\CleanProspecter\Gateway\Entity\PageRequest;

class OrganizationRepositoryAdapter extends RepositoryAdapter implements OrganizationGateway
{
    public function get(mixed $id): Organization
    {
        return $this->findOneBy(['id' => $id]) ?? throw new NotFoundException();
    }

    public function create(Organization $organization): Organization
    {
        $this->save($organization);

        return $organization;
    }

    public function update(mixed $id, Organization $organization): Organization
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
            ->setParameter('query', sprintf('%%%s%%', $pageRequest->getQuery()))
            ->setFirstResult(($pageRequest->getPage() - 1) * $pageRequest->getMaxByPage())
            ->setMaxResults($pageRequest->getMaxByPage());

        $this->applyFilterParameters($pageRequest, $query);

        $paginator = $this->paginatorFactory->create($query);
        $total = $paginator->count();

        return new Page($pageRequest->getPage(), $total, intdiv($total - 1, $pageRequest->getMaxByPage()) + 1, iterator_to_array($paginator));
    }

    private function buildFindPageByQueryDql(PageRequest $pageRequest): string
    {
        $where = '(o.corporateName LIKE :query OR o.email LIKE :query)';

        foreach (array_keys($pageRequest->getFilter()) as $field) {
            $where .= sprintf(' AND o.%s = :%s', $field, $field);
        }

        return sprintf('SELECT o FROM %s o WHERE %s ORDER BY o.id DESC', Organization::class, $where);
    }

    private function applyFilterParameters(PageRequest $pageRequest, Query $query): void
    {
        foreach ($pageRequest->getFilter() as $field => $value) {
            $query->setParameter($field, $value);
        }
    }
}
