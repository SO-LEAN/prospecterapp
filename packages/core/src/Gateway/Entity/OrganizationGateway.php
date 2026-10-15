<?php

declare(strict_types=1);

namespace Prospecter\Core\Gateway\Entity;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Exception\Gateway\UniqueConstraintViolationException;

interface OrganizationGateway
{
    /**
     * @throws NotFoundException
     */
    public function get(int $id): Organization;

    /**
     * @throws UniqueConstraintViolationException
     */
    public function create(Organization $organization): Organization;

    /**
     * @throws UniqueConstraintViolationException
     */
    public function update(int $id, Organization $organization): Organization;

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?Organization;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<Organization>
     */
    public function findBy(array $criteria): array;

    /**
     * @return Page<Organization>
     */
    public function findPageByQuery(PageRequest $pageRequest): Page;
}
