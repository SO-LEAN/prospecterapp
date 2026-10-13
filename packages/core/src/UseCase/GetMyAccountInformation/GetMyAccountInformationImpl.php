<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\GetMyAccountInformation;

use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Gateway\Entity\UserGateway;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class GetMyAccountInformationImpl extends AbstractUseCase implements GetMyAccountInformation
{
    /**
     * @var UserGateway
     */
    private $userGateway;
    /**
     * @var OrganizationGateway
     */
    private $organizationGateway;

    public function __construct(UserGateway $userGateway, OrganizationGateway $organizationGateway)
    {
        $this->userGateway = $userGateway;
        $this->organizationGateway = $organizationGateway;
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_USER'];
    }

    public function execute(GetMyAccountInformationRequest $request, GetMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer)
    {
        $user = $this->getUser($consumer->getUserId());
        $organization = $this->getOrganization($consumer->getOrganizationId());

        return $presenter->present($this->buildResponse($user, $organization));
    }

    private function getOrganization($id): Organization
    {
        try {
            return $this->organizationGateway->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('Organization with #ID %d not found', $id), 404, $e, ['id' => 'Organization not found']);
        }
    }

    private function getUser($id): User
    {
        try {
            return $this->userGateway->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('User with #ID %d not found', $id), 404, $e, ['id' => 'User not found']);
        }
    }

    private function buildResponse(User $user, Organization $organization) : GetMyAccountInformationResponse
    {
        return new GetMyAccountInformationResponse(
            $user->getUserName(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPicture() ? $user->getPicture()->getUrl(): null,
            $user->getPicture() ? $user->getPicture()->getExtension(): null,
            $user->getPicture() ? $user->getPicture()->getSize(): null,
            $user->getPhoneNumber(),
            $user->getEmail(),
            $user->getLanguage(),
            $organization->getCorporateName(),
            $organization->getForm(),
            $organization->getLogo() ? $organization->getLogo()->getUrl() : null,
            $organization->getLogo() ? $organization->getLogo()->getExtension() : null,
            $organization->getLogo() ? $organization->getLogo()->getSize() : null
        );
    }
}
