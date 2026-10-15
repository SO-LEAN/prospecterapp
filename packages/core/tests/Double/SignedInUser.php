<?php

namespace Tests\Core\Double;

use Prospecter\Core\UseCase\UseCaseConsumer;

/**
 * The person using the application in a scenario, already authenticated by the infrastructure.
 */
final readonly class SignedInUser implements UseCaseConsumer
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private int $userId,
        private int $organizationId,
        private array $roles = ['ROLE_USER', 'ROLE_PROSPECTOR'],
    ) {
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }
}
