<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\PrepareCampaign;

use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignBrief;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\DailyCap;
use Prospecter\Core\Campaigns\Domain\Model\MessageMode;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/** A member prepares a campaign for their account, as a draft. */
final readonly class PrepareCampaignHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(PrepareCampaign $command): void
    {
        $member = new MemberId($command->memberId);
        $name = CampaignName::of($command->name);
        $channel = Channel::named($command->channel);
        $campaign = Campaign::prepare(
            new Gesture($this->campaigners->accountOf($member), $member, $command->executedAt),
            new CampaignId($command->campaignId),
            new CampaignBrief($name, Targeting::by($channel, $command->tags), MessageTemplate::of($command->message, $command->subject, $channel, MessageMode::named($command->messageMode))),
            DailyCap::orNone($command->dailyCap),
        );
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
