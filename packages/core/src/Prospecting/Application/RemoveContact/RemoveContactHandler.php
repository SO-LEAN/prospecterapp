<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveContact;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class RemoveContactHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(RemoveContact $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->removeContact($contact, by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
