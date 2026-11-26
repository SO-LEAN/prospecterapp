<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignMessageResource;
use App\Campaigning\Resource\DailyCapResource;
use App\Campaigning\Resource\NextRecipientResource;
use App\Contacts\ContactsInList;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Recipient\NextRecipient\NextRecipient;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes a campaign's next recipient (ADR 0217). Campaigns gives the recipient, renders their message and links what
 * to do with them. PersonalData, Prospecting and Campaigns then give their contact in a list, joined by its id. A
 * recipient to handle is a contact that PersonalData shows to the member who manages personal data.
 */
final readonly class NextRecipientComposition
{
    private const string NO_CLOCK = 'UTC';

    public function __construct(
        private QueryHandler $nextRecipient,
        private ContactsInList $contactsInList,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param ?string $timeZone the member's clock, on which the daily cap's day is counted; UTC when null
     */
    public function read(string $reader, string $campaignId, ?string $timeZone = null): NextRecipientResource
    {
        $presenter = new NextRecipientPresenter($this->links);
        $this->nextRecipient->__invoke(new NextRecipient(memberId: $reader, campaignId: $campaignId, timeZone: $timeZone ?? self::NO_CLOCK, at: $this->clock->now()), $presenter);
        $next = $presenter->recipient;

        return new NextRecipientResource(
            campaignId: $next->campaignId,
            contact: $this->contactsInList->of($reader, [$next->contactId])[$next->contactId] ?? throw new \LogicException('The next recipient is not read'),
            channel: $next->channel,
            message: new CampaignMessageResource($next->subject, $next->text),
            messageMode: $next->messageMode,
            instruction: $presenter->instruction,
            pending: $next->pending,
            dailyCap: null === $next->dailyCap ? null : new DailyCapResource($next->dailyCap, $next->processedToday, $next->dailyCapReached),
            warning: $presenter->warning,
            links: $presenter->links,
        );
    }
}
