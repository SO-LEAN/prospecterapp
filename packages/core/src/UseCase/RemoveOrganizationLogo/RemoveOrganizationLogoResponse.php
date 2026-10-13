<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

final class RemoveOrganizationLogoResponse
{
    /**
     * @var mixed
     */
    private $organizationId;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    /**
     * @return mixed
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }
}
