<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeLogo extends UseCase
{
    private readonly OrganizationResponses $responses;
    private readonly FileStore $files;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users, Storage $storage)
    {
        $this->responses = new OrganizationResponses($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeLogoRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = $this->organizations->get($request->id);
        $organization->changeLogo($this->files->store($request->logo), by: $prospector);
        $this->organizations->save($organization);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
