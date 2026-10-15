<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\NotFound;

final class UnknownOrganization extends NotFound
{
    public function __construct(OrganizationId $id)
    {
        parent::__construct(sprintf('Organization %s not found', $id));
    }
}
