<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\WhichRecipientsArePending;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\PendingRecipientsResponse;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePending;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePendingOutput;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePendingService;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class WhichRecipientsArePendingHandler implements QueryHandler, WhichRecipientsArePendingService
{
    public function __construct(private CampaignSheetRetriever $campaignSheetRetriever)
    {
    }

    public function __invoke(WhichRecipientsArePending $query, WhichRecipientsArePendingOutput $output): void
    {
        $campaign = $this->campaignSheetRetriever->find(new AccountId($query->accountId), new CampaignId($query->campaignId));

        if (null !== $campaign) {
            $output->present(self::answered($campaign, $query->personIds));
        }
    }

    /**
     * @param list<string> $personIds
     */
    private static function answered(CampaignSheet $campaign, array $personIds): PendingRecipientsResponse
    {
        $recipients = array_values(array_filter($personIds, static fn (string $id): bool => isset($campaign->recipients[$id])));

        return new PendingRecipientsResponse(
            $campaign->id,
            $campaign->channel,
            $campaign->running,
            $recipients,
            array_values(array_filter($recipients, static fn (string $id): bool => $campaign->recipients[$id]->pending)),
        );
    }
}
