<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\GetOrganization;

use Tests\Core\Base\Builder;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;

class GetOrganizationRequestBuilder extends Builder
{
    public function __construct()
    {
        parent::__construct();
        $this->withRegularData();
    }

    public function withRegularData(): self
    {
        return $this
            ->with('id', 777);
    }

    protected function getTargetClass(): string
    {
        return GetOrganizationRequest::class;
    }

    protected function getTargetType(): string
    {
        return 'dto';
    }
}
