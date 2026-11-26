<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients;

use Prospecter\Core\Campaigns\Application\RecipientInSheet;

/**
 * A campaign's recipients as the team reads them, and what the campaign's state allows on them.
 */
final readonly class CampaignRecipients
{
    /**
     * @param array<string, RecipientInSheet> $recipients      each recipient by contact id, in the order added
     * @param list<string>                    $possibleIntents the names of the intents the campaign's state allows
     */
    public function __construct(
        public string $campaignId,
        public array $recipients,
        public array $possibleIntents,
    ) {
    }
}
