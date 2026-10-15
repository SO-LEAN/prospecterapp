<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\RemoveLogo;

use Prospecter\Core\Application\Organization\OrganizationPresenter;
use Prospecter\Core\Application\Organization\OrganizationResponses;
use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class RemoveLogo extends UseCase
{
    private readonly OrganizationResponses $responses;
    private readonly FileStore $files;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users, Storage $storage)
    {
        $this->responses = new OrganizationResponses($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(RemoveLogoRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = $this->organizations->get($request->id);
        $removedLogo = $organization->removeLogo(by: $prospector);
        $this->organizations->save($organization);
        $this->files->discard($removedLogo);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
