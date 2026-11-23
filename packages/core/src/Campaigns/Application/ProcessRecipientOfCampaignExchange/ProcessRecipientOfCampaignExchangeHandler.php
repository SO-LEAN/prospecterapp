<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ProcessRecipientOfCampaignExchange;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\RecipientOutcome;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Marks the recipient as done or missed when Prospecting logs their exchange in a campaign (ADR 0145). The campaign
 * ends once nobody is left to handle. Nothing changes when the command comes again, or when it is about an erased
 * campaign or a recipient already handled.
 */
final readonly class ProcessRecipientOfCampaignExchangeHandler implements CommandHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ProcessRecipientOfCampaignExchange $command): void
    {
        $campaign = $this->campaigns->find(new AccountId($command->accountId), new CampaignId($command->campaignId));

        if (null !== $campaign) {
            $campaign->process(new ContactId($command->contactId), $command->exchangeId, self::outcomeOf($command->outcome), $command->executedAt);
            $this->campaigns->save($campaign);
        }
    }

    private static function outcomeOf(string $outcome): RecipientOutcome
    {
        return 'missed' === $outcome ? RecipientOutcome::Missed : RecipientOutcome::Done;
    }
}
