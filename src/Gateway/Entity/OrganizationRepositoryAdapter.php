<?php

namespace App\Gateway\Entity;

use Doctrine\ORM\Query;
use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\OrganizationGateway;
use Solean\CleanProspecter\Gateway\Entity\Page;
use Solean\CleanProspecter\Gateway\Entity\PageRequest;

/**
 * Class OrganizationGatewayAdapter.
 */
class OrganizationRepositoryAdapter extends RepositoryAdapter implements OrganizationGateway
{
    /**
     * @param $id
     */
    public function get($id): Organization
    {
        /** @var Organization $Organization */
        $Organization = $this->repository->findOneBy(['id' => $id]);

        if (null === $Organization) {
            throw new NotFoundException();
        }

        return $Organization;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(Organization $Organization): Organization
    {
        $this->save($Organization);

        return $Organization;
    }

    /**
     * @param $id
     */
    public function update($id, Organization $Organization): Organization
    {
        $Organization->setId($id);
        $this->save($Organization);

        return $Organization;
    }

    public function findOneBy(array $criteria): ?Organization
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository->findOneBy($criteria);
    }

    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }

    public function findPageByQuery(PageRequest $pageRequest): Page
    {
        $dql = $this->buildFindPageByQueryDql($pageRequest);
        $query = $this->entityManager->createQuery($dql)
            ->setParameter('query', sprintf('%%%s%%', $pageRequest->getQuery()))
            ->setFirstResult(($pageRequest->getPage() - 1) * $pageRequest->getMaxByPage())
            ->setMaxResults($pageRequest->getMaxByPage());

        $this->ApplyFilterParameter($pageRequest, $query);

        $pg = $this->paginatorFactory->create($query);

        return new Page($pageRequest->getPage(), $pg->count(), intdiv($pg->count() - 1, $pageRequest->getMaxByPage()) + 1, $pg->getIterator()->getArrayCopy());
    }

    private function buildFindPageByQueryDql(PageRequest $pageRequest): string
    {
        $from = 'Solean\CleanProspecter\Entity\Organization o ';
        $where = '(o.corporateName LIKE :query OR o.email LIKE :query)';
        $where = $this->applyFilter($pageRequest, $where);
        $orderBy = 'o.id DESC';
        $dql = sprintf('SELECT o FROM %s WHERE %s ORDER BY %s', $from, $where, $orderBy);

        return $dql;
    }

    /**
     * @param string $where
     */
    private function applyFilter(PageRequest $pageRequest, $where): string
    {
        foreach ($pageRequest->getFilter() as $field => $value) {
            $where .= sprintf(' AND o.%s = :%s', $field, $field);
        }

        return $where;
    }

    /**
     * @param $query
     */
    private function ApplyFilterParameter(PageRequest $pageRequest, Query $query): void
    {
        foreach ($pageRequest->getFilter() as $field => $value) {
            $query->setParameter($field, $value);
        }
    }
}
