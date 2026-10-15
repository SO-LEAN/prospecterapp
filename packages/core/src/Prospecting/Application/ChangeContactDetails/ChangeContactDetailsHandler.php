<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeContactDetailsHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ChangeContactDetails $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->changeContactDetails(new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
