<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\RemoveOrganizationLogo;

use Tests\Core\Base\Builder;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoResponse;

class RemoveOrganizationLogoResponseBuilder extends Builder
{
    public function __construct()
    {
        parent::__construct();
        $this->withRegularData();
    }

    public function withRegularData(): self
    {
        return $this->with('organizationId', 123);
    }

    protected function getTargetClass(): string
    {
        return RemoveOrganizationLogoResponse::class;
    }

    protected function getTargetType(): string
    {
        return 'dto';
    }
}
