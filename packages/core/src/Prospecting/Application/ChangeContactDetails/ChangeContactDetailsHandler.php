<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class ChangeContactDetailsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private EmailUniqueness $emails,
    ) {
    }

    public function __invoke(ChangeContactDetails $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->changeContactDetails(new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language), by: $author, at: $command->executedAt);
        $this->emails->ensureAvailableFor($organization);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
