<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\DismissRecipient;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A team member dismisses a recipient of a started campaign of their account. The campaign ends once nobody is left to
 * handle.
 */
final readonly class DismissRecipientHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Events $events,
    ) {
    }

    public function __invoke(DismissRecipient $command): void
    {
        $member = new MemberId($command->memberId);
        $campaign = $this->campaigns->get($this->campaigners->accountOf($member), new CampaignId($command->campaignId));
        $campaign->dismiss(new ContactId($command->contactId), self::reasonOf($command->reason), by: $member, at: $command->executedAt);
        $this->campaigns->save($campaign);
        $this->publishWhatHappenedTo($campaign);
    }

    private static function reasonOf(?string $typed): ?string
    {
        $reason = trim($typed ?? '');

        return '' === $reason ? null : $reason;
    }
}
