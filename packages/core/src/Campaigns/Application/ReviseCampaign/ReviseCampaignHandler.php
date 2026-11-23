<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReviseCampaign;

use Prospecter\Core\Campaigns\Domain\Model\CampaignBrief;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageMode;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/** A team member changes a draft campaign of their account, whoever prepared it. */
final readonly class ReviseCampaignHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(ReviseCampaign $command): void
    {
        $member = new MemberId($command->memberId);
        $campaign = $this->campaigns->get($this->campaigners->accountOf($member), new CampaignId($command->campaignId));
        $name = CampaignName::of($command->name);
        $channel = Channel::named($command->channel);
        $campaign->revise(
            new CampaignBrief($name, Targeting::by($channel, $command->tags), MessageTemplate::of($command->message, $command->subject, $channel, MessageMode::named($command->messageMode))),
            $member,
            $command->executedAt,
        );
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
