<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

/**
 * Who executes a use case, already authenticated by the delivery.
 */
interface UseCaseConsumer
{
    /**
     * @return list<string>
     */
    public function getRoles(): array;

    public function getUserId(): UserId;

    public function getOrganizationId(): OrganizationId;
}
