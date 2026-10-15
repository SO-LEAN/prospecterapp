<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Application\Service\FileStore;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;

final class RemoveLogo extends OrganizationUseCase
{
    private readonly FileStore $files;

    public function __construct(OrganizationRepository $organizations, Storage $storage)
    {
        parent::__construct($organizations);
        $this->files = new FileStore($storage);
    }

    public function execute(RemoveLogoRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $removedLogo = $organization->removeLogo();
        $this->organizations->save($organization);
        $this->files->discard($removedLogo);

        return $this->present($organization, $presenter);
    }
}
