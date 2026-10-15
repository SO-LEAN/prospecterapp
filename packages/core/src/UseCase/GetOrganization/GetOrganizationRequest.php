<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetOrganization;

final readonly class GetOrganizationRequest
{
    public function __construct(public int $id)
    {
    }
}
