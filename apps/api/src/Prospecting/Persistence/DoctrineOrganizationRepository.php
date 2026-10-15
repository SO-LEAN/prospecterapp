<?php

namespace App\Prospecting\Persistence;

use App\Shared\PaginatorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
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

    public function save(Organization $organization): void
    {
        $this->ensureTheEmailIsFreeInTheBaseOf($organization);
        $this->prospectingEntityManager->persist($organization);
        $this->prospectingEntityManager->flush();
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

    /**
     * Two organizations of the same prospecting base cannot share their general email; other accounts may.
     */
    private function ensureTheEmailIsFreeInTheBaseOf(Organization $organization): void
    {
        $email = $organization->contactDetails()->email;

        if (null === $email) {
            return;
        }

        $taken = (int) $this->prospectingEntityManager->createQuery(sprintf('SELECT COUNT(o.id) FROM %s o WHERE o.accountId = :account AND o.contactDetails.email = :email AND o.id != :id', Organization::class))
            ->setParameter('account', $organization->accountId(), 'account_id')
            ->setParameter('email', $email, 'email')
            ->setParameter('id', $organization->id(), 'organization_id')
            ->getSingleScalarResult();

        if ($taken > 0) {
            throw EmailAlreadyUsed::byAnother();
        }
    }
}
