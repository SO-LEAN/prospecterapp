<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

use Prospecter\Core\Prospecting\Domain\EmailUniqueness;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeContactDetailsHandler implements CommandHandler
{
    private EmailUniqueness $emails;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
        $this->emails = new EmailUniqueness($organizations);
    }

    public function __invoke(ChangeContactDetails $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->changeContactDetails(new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language), by: $prospector, at: $command->executedAt);
        $this->emails->ensureAvailableFor($organization);
        $this->organizations->save($organization);
    }
}
