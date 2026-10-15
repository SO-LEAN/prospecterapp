<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase;

/**
 * Who executes a use case, already authenticated by the delivery.
 */
interface UseCaseConsumer
{
    /**
     * @return list<string>
     */
    public function getRoles(): array;

    public function getUserId(): int;

    public function getOrganizationId(): int;
}
