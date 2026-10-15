<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeLogo;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Storage;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ChangeLogoHandler implements CommandHandler
{
    private FileStore $files;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, Storage $storage)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangeLogo $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->changeLogo($this->files->store($command->logo), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
