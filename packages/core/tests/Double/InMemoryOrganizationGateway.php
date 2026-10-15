<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Entity\Page;
use Prospecter\Core\Gateway\Entity\PageRequest;

final readonly class InMemoryOrganizationGateway implements OrganizationGateway
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(int $id): Organization
    {
        $organization = $this->database->find($id);

        return $organization instanceof Organization ? $organization : throw new NotFoundException(sprintf('No organization #%s', $id));
    }

    public function create(Organization $organization): Organization
    {
        $this->database->insert($organization);

        return $organization;
    }

    public function update(int $id, Organization $organization): Organization
    {
        $this->database->replace($id, $organization);

        return $organization;
    }

    public function findOneBy(array $criteria): ?Organization
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /**
     * @return list<Organization>
     */
    public function findBy(array $criteria): array
    {
        return array_values(array_filter(
            $this->database->all(Organization::class),
            static fn (Organization $organization): bool => Criteria::match($organization, $criteria),
        ));
    }

    /**
     * Same contract as the Doctrine adapter: the query searches the corporate name and the email,
     * the filter compares fields (ownedBy by id), newest first.
     */
    public function findPageByQuery(PageRequest $pageRequest): Page
    {
        $query = mb_strtolower($pageRequest->query);
        $matching = array_filter(
            $this->database->all(Organization::class),
            static fn (Organization $organization): bool => Criteria::match($organization, $pageRequest->filter)
                && ('' === $query
                    || str_contains(mb_strtolower((string) $organization->getCorporateName()), $query)
                    || str_contains(mb_strtolower((string) $organization->getEmail()), $query)),
        );
        usort($matching, static fn (Organization $a, Organization $b): int => $b->getId() <=> $a->getId());

        $total = count($matching);
        $maxByPage = $pageRequest->maxByPage;
        $content = array_slice($matching, ($pageRequest->page - 1) * $maxByPage, $maxByPage);

        return new Page($pageRequest->page, $total, intdiv($total - 1, $maxByPage) + 1, $content);
    }
}
