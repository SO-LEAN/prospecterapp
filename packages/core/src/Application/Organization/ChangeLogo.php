<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;

final class ChangeLogo extends OrganizationUseCase
{
    private readonly FileStore $files;

    public function __construct(OrganizationRepository $organizations, Storage $storage)
    {
        parent::__construct($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(ChangeLogoRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->changeLogo($this->files->store($request->logo));

        return $this->saveAndPresent($organization, $presenter);
    }
}
