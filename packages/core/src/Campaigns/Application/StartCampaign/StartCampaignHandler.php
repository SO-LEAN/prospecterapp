<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\StartCampaign;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member of the team starts a frozen campaign of their account: its recipients and its message no longer change.
 */
final readonly class StartCampaignHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(StartCampaign $command): void
    {
        $member = new MemberId($command->memberId);
        $campaign = $this->campaigns->get($this->campaigners->accountOf($member), new CampaignId($command->campaignId));
        $campaign->start(by: $member, at: $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
