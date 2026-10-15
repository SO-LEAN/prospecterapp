<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeMyOrganizationLogo extends UseCase
{
    private readonly AccountResponses $responses;
    private readonly FileStore $files;

    public function __construct(private readonly UserRepository $users, private readonly OrganizationRepository $organizations, Storage $storage)
    {
        $this->responses = new AccountResponses($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeMyOrganizationLogoRequest $request, AccountPresenter $presenter): mixed
    {
        $me = $this->users->get($request->userId);
        $myOrganization = $this->organizations->get($me->organizationId());
        $myOrganization->changeLogo($this->files->store($request->logo), by: $me);
        $this->organizations->save($myOrganization);

        return $presenter->present($this->responses->of($me));
    }
}
