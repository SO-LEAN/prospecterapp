<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

final class UnknownOrganization extends \RuntimeException
{
    public function __construct(OrganizationId $id)
    {
        parent::__construct(sprintf('Organization %s not found', $id));
    }
}
