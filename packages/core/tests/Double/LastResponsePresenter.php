<?php

namespace Tests\Core\Double;

use LogicException;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationPresenter;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationPresenter;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoPresenter;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationPresenter;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationPresenter;

/**
 * The output port of every use case: keeps what the use case presented, as a delivery would.
 */
final class LastResponsePresenter implements CreateOrganizationPresenter, FindMyOwnOrganizationsPresenter, GetMyAccountInformationPresenter, GetOrganizationPresenter, RemoveOrganizationLogoPresenter, UpdateMyAccountInformationPresenter, UpdateOrganizationPresenter
{
    private ?object $response = null;

    public function present(object $response): object
    {
        return $this->response = $response;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function response(string $class): object
    {
        return $this->response instanceof $class ? $this->response : throw new LogicException(sprintf('Expected a %s to be presented, got %s', $class, get_debug_type($this->response)));
    }
}
