<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\AddRecipient;

use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\Candidate;
use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member of the team adds a contact of their account they chose to a frozen campaign, when it has the means of its
 * channel; the refusal names them as the member reads them.
 */
final readonly class AddRecipientHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Addressees $addressees,
        private ContactTagsRetriever $contactTagsRetriever,
        private Events $events,
    ) {
    }

    public function __invoke(AddRecipient $command): void
    {
        $member = new MemberId($command->memberId);
        $accountId = $this->campaigners->accountOf($member);
        $campaign = $this->campaigns->get($accountId, new CampaignId($command->campaignId));
        $contact = new PersonId($command->contactId);
        $addressee = $this->addressees->addressee($accountId, $member, $contact);
        $tags = $this->contactTagsRetriever->sourcesOf($accountId, [$contact])[0]->tags();
        $campaign->addRecipient(new Candidate($contact, $tags, $addressee), $member, $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
