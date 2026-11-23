<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RenderCampaignMessage;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/** The core renders the message, for the member's screen and for every other client that reads a campaign. */
final readonly class RenderCampaignMessageHandler implements QueryHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Addressees $addressees,
    ) {
    }

    public function __invoke(RenderCampaignMessage $query, RenderCampaignMessageOutput $output): void
    {
        $member = new MemberId($query->memberId);
        $accountId = $this->campaigners->accountOf($member);
        $campaign = $this->campaigns->get($accountId, new CampaignId($query->campaignId));
        $message = $campaign->messageFor($this->addressees->addressee($accountId, $member, new ContactId($query->contactId)));

        $output->present(new CampaignMessage($query->campaignId, $query->contactId, $message->subject, $message->text));
    }
}
