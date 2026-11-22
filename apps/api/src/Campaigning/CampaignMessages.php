<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignMessageResource;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\KeptCampaignMessage;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\RenderCampaignMessage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The message of a campaign, rendered by the core for one of its contacts.
 */
final readonly class CampaignMessages
{
    public function __construct(private QueryHandler $renderCampaignMessage)
    {
    }

    public function renderedFor(string $memberId, string $campaignId, string $contactId): CampaignMessageResource
    {
        $kept = new KeptCampaignMessage();
        ($this->renderCampaignMessage)(new RenderCampaignMessage($memberId, $campaignId, $contactId), $kept);
        $message = $kept->message ?? throw new \LogicException('The message was not presented');

        return new CampaignMessageResource($message->subject, $message->text);
    }
}
