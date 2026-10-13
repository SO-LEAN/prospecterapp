<?php

declare(strict_types = 1);

namespace Prospecter\Core\UseCase;

interface UseCaseConsumer
{
    public function getRoles(): array;
    public function getUserId();
    public function getOrganizationId();
}
