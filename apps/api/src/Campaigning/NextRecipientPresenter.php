<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\NextRecipientLinksResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Recipient\NextRecipient\NextRecipientOutput;
use Prospecter\Core\Campaigns\Application\Recipient\NextRecipient\RecipientToHandle;

/**
 * Presents what Campaigns shows of a campaign's next recipient: the recipient, the links to log the exchange held with
 * them or to dismiss them, and how an AI treats the message, worded from the mode the core returns. Prospecter sends
 * nothing (ADR 0144). One presenter serves one call (ADR 0085).
 */
final class NextRecipientPresenter implements NextRecipientOutput
{
    private const array INSTRUCTIONS = [
        'asIs' => 'Send the message as is, without changing a word, with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you sent.',
        'adapted' => 'You may rewrite the message for this person, from what you know of them (their profile, your past exchanges), but always keep the message\'s idea. Send it with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you actually sent.',
    ];

    private const string DAILY_CAP_REACHED = 'Today\'s cap is reached (%d). Stop, and ask your member whether to go beyond it before you send this message.';

    public private(set) RecipientToHandle $recipient;

    public private(set) NextRecipientLinksResource $links;

    public private(set) string $instruction;

    public private(set) ?string $warning = null;

    public function __construct(private readonly Links $routes)
    {
    }

    public function present(RecipientToHandle $recipient): void
    {
        $ids = ['id' => $recipient->campaignId, 'contactId' => $recipient->contactId];
        $this->recipient = $recipient;
        $this->links = new NextRecipientLinksResource(
            noteExchange: $this->routes->to('api_campaigns_note_exchange', $ids),
            dismiss: $this->routes->to('api_campaigns_dismiss_recipient', $ids),
        );
        $this->instruction = self::INSTRUCTIONS[$recipient->messageMode];
        $this->warning = $recipient->dailyCapReached ? sprintf(self::DAILY_CAP_REACHED, $recipient->dailyCap) : null;
    }
}
