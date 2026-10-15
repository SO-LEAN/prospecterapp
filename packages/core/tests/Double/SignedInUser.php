<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\UserId;

/**
 * The person using the application in a scenario, already authenticated by the infrastructure.
 */
final readonly class SignedInUser implements UseCaseConsumer
{
    /** @var list<string> */
    private array $roles;

    public function __construct(
        private UserId $userId,
        private OrganizationId $organizationId,
        Role ...$roles,
    ) {
        $this->roles = array_map(static fn (Role $role): string => $role->value, $roles);
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getOrganizationId(): OrganizationId
    {
        return $this->organizationId;
    }
}
