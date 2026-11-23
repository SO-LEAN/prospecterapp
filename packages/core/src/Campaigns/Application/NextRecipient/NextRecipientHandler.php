<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NextRecipient;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Port\CampaignExchanges;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Asks the campaign itself, restored by its repository, as `RenderCampaignMessage` does. Its next recipient and its
 * message are the aggregate's rules. The campaign is read again at each call, so a recipient handled meanwhile on the
 * screen is no longer the next one. A recipient with whom Prospecting logged an exchange of the campaign is not the
 * next one either, even before the reaction processes them (ADR 0195). Once the daily cap is reached on the member's
 * clock, the recipient is still given, with a warning: the cap is only a guard.
 */
final readonly class NextRecipientHandler implements QueryHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private Campaigners $campaigners,
        private Addressees $addressees,
        private CampaignExchanges $campaignExchanges,
    ) {
    }

    public function __invoke(NextRecipient $query, NextRecipientOutput $output): void
    {
        $member = new MemberId($query->memberId);
        $accountId = $this->campaigners->accountOf($member);
        $campaign = $this->campaigns->get($accountId, new CampaignId($query->campaignId));
        $exchangedWith = $this->campaignExchanges->exchangedWith($accountId, $campaign->id(), $campaign->pendingRecipients());
        $contact = $campaign->nextRecipient($exchangedWith);
        $message = $campaign->messageFor($this->addressees->addressee($accountId, $member, $contact));
        $processedToday = $campaign->processedOnTheDayOf($query->at, new \DateTimeZone($query->timeZone));
        $dailyCap = $campaign->dailyCap();

        $output->present(new RecipientToHandle(
            $query->campaignId,
            $contact->value,
            $campaign->channel()->value,
            $message->subject,
            $message->text,
            $campaign->messageMode()->value,
            $campaign->stillToHandle($exchangedWith),
            $dailyCap?->sends,
            $processedToday,
            $dailyCap?->isReachedBy($processedToday) ?? false,
        ));
    }
}
