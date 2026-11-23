<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownRecipient;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * A campaign's recipient. The exchange is kept for the contact's organizations and for its campaign (ADR 0145).
 */
final readonly class RecipientCorrespondent extends Correspondent
{
    /**
     * @param list<OrganizationId> $organizationsOfTheContact
     */
    public function __construct(private RecipientInCampaign $recipient, array $organizationsOfTheContact)
    {
        parent::__construct($recipient->contact, $recipient->contact, $organizationsOfTheContact);
    }

    /**
     * The recipient must still be pending in a running campaign.
     *
     * @throws CampaignNotRunning|UnknownRecipient|RecipientAlreadyProcessed
     */
    #[\Override]
    public function ensureToHandle(): void
    {
        $this->recipient->ensureToHandle();
    }

    #[\Override]
    public function campaign(): CampaignId
    {
        return $this->recipient->campaign;
    }
}
