<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\DiscardCampaign;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/** A team member discards a campaign of their account that never started. */
final readonly class DiscardCampaignHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(DiscardCampaign $command): void
    {
        $member = new MemberId($command->memberId);
        $campaign = $this->campaigns->get($this->campaigners->accountOf($member), new CampaignId($command->campaignId));
        $campaign->discard(by: $member, at: $command->executedAt);
        $this->campaigns->remove($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
