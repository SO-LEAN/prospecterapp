<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Application\Account\AccountPresenter;
use Prospecter\Core\Application\Organization\OrganizationPresenter;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsPresenter;

/**
 * The output port of every use case: keeps what the use case presented, as a delivery would.
 */
final class LastResponsePresenter implements AccountPresenter, OrganizationPresenter, SearchProspectedOrganizationsPresenter
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
        return $this->response instanceof $class ? $this->response : throw new \LogicException(sprintf('Expected a %s to be presented, got %s', $class, get_debug_type($this->response)));
    }
}
