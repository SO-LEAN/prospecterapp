<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

use Prospecter\Core\Domain\Shared\NotFound;

final class UnknownOrganization extends NotFound
{
    public function __construct(OrganizationId $id)
    {
        parent::__construct(sprintf('Organization %s not found', $id));
    }
}
