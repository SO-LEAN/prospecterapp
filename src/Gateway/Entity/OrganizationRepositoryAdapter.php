<?php

namespace App\Gateway\Entity;

use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\OrganizationGateway;

/**
 * Class OrganizationGatewayAdapter.
 */
class OrganizationRepositoryAdapter extends RepositoryAdapter implements OrganizationGateway
{
    /**
     * @param $id
     *
     * @return Organization
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
     * @param Organization $Organization
     *
     * @return Organization
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(Organization $Organization): Organization
    {
        $this->save($Organization);

        return $Organization;
    }

    /**
     * @param $id
     * @param Organization $Organization
     *
     * @return Organization
     */
    public function update($id, Organization $Organization): Organization
    {
        $Organization->setId($id);
        $this->save($Organization);

        return $Organization;
    }

    /**
     * @param array $criteria
     *
     * @return null|Organization
     */
    public function findOneBy(array $criteria): ?Organization
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository->findOneBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }
}
