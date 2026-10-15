<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetMyAccountInformation;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Entity\UserGateway;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class GetMyAccountInformationImpl extends AbstractUseCase implements GetMyAccountInformation
{
    public function __construct(
        private readonly UserGateway $users,
        private readonly OrganizationGateway $organizations,
    ) {
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_USER'];
    }

    public function execute(GetMyAccountInformationRequest $request, GetMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        return $presenter->present($this->buildResponse($this->user($consumer->getUserId()), $this->organization($consumer->getOrganizationId())));
    }

    private function user(int $id): User
    {
        try {
            return $this->users->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('User with #ID %d not found', $id), ['id' => 'User not found'], $e);
        }
    }

    private function organization(int $id): Organization
    {
        try {
            return $this->organizations->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('Organization with #ID %d not found', $id), ['id' => 'Organization not found'], $e);
        }
    }

    private function buildResponse(User $user, Organization $organization): GetMyAccountInformationResponse
    {
        return new GetMyAccountInformationResponse(
            userName: $user->getUserName(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            picture: $user->getPicture(),
            phoneNumber: $user->getPhoneNumber(),
            email: $user->getEmail(),
            language: $user->getLanguage(),
            organizationCorporateName: $organization->getCorporateName(),
            organizationForm: $organization->getForm(),
            organizationLogo: $organization->getLogo(),
        );
    }
}
