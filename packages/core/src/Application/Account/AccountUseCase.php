<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\Exception\NotFoundException;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\Organization\UnknownOrganization;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\UnknownUser;
use Prospecter\Core\Domain\User\User;
use Prospecter\Core\Domain\User\UserRepository;

/**
 * An intention of a user on its own account or on its own organization.
 */
abstract class AccountUseCase extends UseCase
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly OrganizationRepository $organizations,
    ) {
    }

    public function canBeExecutedBy(): array
    {
        return [Role::User];
    }

    protected function me(UseCaseConsumer $consumer): User
    {
        try {
            return $this->users->get($consumer->getUserId());
        } catch (UnknownUser $e) {
            throw new NotFoundException($e->getMessage(), ['id' => 'User not found'], $e);
        }
    }

    protected function myOrganization(UseCaseConsumer $consumer): Organization
    {
        try {
            return $this->organizations->get($consumer->getOrganizationId());
        } catch (UnknownOrganization $e) {
            throw new NotFoundException($e->getMessage(), ['id' => 'Organization not found'], $e);
        }
    }

    protected function present(UseCaseConsumer $consumer, AccountPresenter $presenter): mixed
    {
        $me = $this->me($consumer);
        $myOrganization = $this->myOrganization($consumer);

        return $presenter->present(new AccountResponse(
            userName: $me->userName(),
            name: $me->name(),
            contactDetails: $me->contactDetails(),
            picture: $me->picture(),
            organizationIdentity: $myOrganization->legalIdentity(),
            organizationLogo: $myOrganization->logo(),
        ));
    }
}
