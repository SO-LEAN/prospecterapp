<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NextRecipient;

use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Port\CampaignExchanges;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Asks the campaign itself, restored by its repository, as `RenderCampaignMessage` does: its next recipient and its
 * message are rules of the aggregate. Read again at each call, a recipient handled meanwhile on the screen is no
 * longer the next; nor is one Prospecting noted an exchange of the campaign with, before its reaction processes them
 * (ADR 0195). Its daily cap reached on the member's clock, the recipient is still given, with the warning: a guard
 * only.
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
