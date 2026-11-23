<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\FreezeCampaign;

use Prospecter\Core\Campaigns\Application\TagsOfEveryContact;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/** A team member freezes a draft campaign. The contacts it targets now become its recipients for good. */
final readonly class FreezeCampaignHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private TagsOfEveryContact $tagsOfEveryContact,
        private Events $events,
    ) {
    }

    public function __invoke(FreezeCampaign $command): void
    {
        $member = new MemberId($command->memberId);
        $accountId = $this->campaigners->accountOf($member);
        $campaign = $this->campaigns->get($accountId, new CampaignId($command->campaignId));
        $campaign->freeze($this->tagsOfEveryContact->of($accountId), by: $member, at: $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
