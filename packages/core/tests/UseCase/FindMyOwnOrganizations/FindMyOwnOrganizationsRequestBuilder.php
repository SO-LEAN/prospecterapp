<?php

declare(strict_types = 1);

namespace Tests\Core\UseCase\FindMyOwnOrganizations;

use Tests\Core\Base\Builder;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;

class FindMyOwnOrganizationsRequestBuilder extends Builder
{
    public function __construct()
    {
        parent::__construct();
        $this->withRegularData();
    }

    public function withRegularData(): self
    {
        $this
            ->with('page', 1)
            ->with('maxByPage', 10)
            ->with('query', 'my query')
        ;

        return $this;
    }

    protected function getTargetClass(): string
    {
        return FindMyOwnOrganizationsRequest::class;
    }

    protected function getTargetType(): string
    {
        return 'dto';
    }
}
