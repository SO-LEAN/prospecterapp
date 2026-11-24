<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\AttachCollectedContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact collected in PersonalData joins the organizations it was collected for, and its collector is the
 * author. A client may have attached the contact to an organization before this command ran. That organization
 * already names the contact, so it stays as it is (ADR 0177).
 */
final readonly class AttachCollectedContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private Events $events)
    {
    }

    public function __invoke(AttachCollectedContact $command): void
    {
        $collector = new MemberId($command->memberId);
        $contact = new ContactId($command->contactId);

        foreach ($command->organizationIds as $organizationId) {
            $organization = $this->organizations->get(new OrganizationId($organizationId));

            if ($organization->hasContact($contact)) {
                continue;
            }

            $organization->addContact($contact, by: $collector, at: $command->executedAt);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
