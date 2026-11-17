<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RemoveRecipient;

use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member of the team takes a recipient off a frozen campaign, never the last one.
 */
final readonly class RemoveRecipientHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(RemoveRecipient $command): void
    {
        $member = new MemberId($command->memberId);
        $campaign = $this->campaigns->get($this->campaigners->accountOf($member), new CampaignId($command->campaignId));
        $campaign->removeRecipient(new PersonId($command->contactId), by: $member, at: $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
