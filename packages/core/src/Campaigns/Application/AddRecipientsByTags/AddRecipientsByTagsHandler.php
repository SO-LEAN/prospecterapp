<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\AddRecipientsByTags;

use Prospecter\Core\Campaigns\Application\TagsOfEveryContact;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A team member adds the contacts that another search finds to a frozen campaign. A contact who is already a recipient
 * is kept once.
 */
final readonly class AddRecipientsByTagsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private TagsOfEveryContact $tagsOfEveryContact,
        private Events $events,
    ) {
    }

    public function __invoke(AddRecipientsByTags $command): void
    {
        $member = new MemberId($command->memberId);
        $accountId = $this->campaigners->accountOf($member);
        $campaign = $this->campaigns->get($accountId, new CampaignId($command->campaignId));
        $campaign->addRecipientsCarrying($command->tags, $this->tagsOfEveryContact->of($accountId), by: $member, at: $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }
}
