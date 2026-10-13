<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\RefreshUser;

use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\Gateway\Entity\UserGateway;

final class RefreshUserImpl extends AbstractUseCase implements RefreshUser
{
    /**
     * @var UserGateway
     */
    private $userGateway;

    public function __construct(UserGateway $userGateway)
    {
        $this->userGateway = $userGateway;
    }

    public function execute(RefreshUserRequest $request, RefreshUserPresenter $presenter)
    {
        /**
         * @var ?User $user
         */
        $user = $this->userGateway->findOneBy(['userName' => $request->getLogin()]);

        if ($user) {
            return $presenter->present(
                new RefreshUserResponse(
                    $user->getId(),
                    $user->getRoles(),
                    $user->getUserName(),
                    $user->getPassword(),
                    $user->getOrganization()->getId(),
                    $user->getPicture()? $user->getPicture()->getUrl() : null
                )
            );
        }

        return null;
    }
}
