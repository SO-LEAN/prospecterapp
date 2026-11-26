<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\RecipientLinksResource;
use App\Campaigning\Resource\RecipientListLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients\CampaignRecipients;
use Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients\ListCampaignRecipientsOutput;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;

/**
 * Presents what Campaigns shows of a campaign's recipients: each one's links, and the list's. In a started campaign, a
 * recipient to handle links to the exchange to log for them and to their dismissal (ADR 0145). A processed recipient
 * links to the exchange logged for them (ADR 0152). One presenter serves one call (ADR 0085).
 */
final class RecipientListPresenter implements ListCampaignRecipientsOutput
{
    public private(set) CampaignRecipients $recipients;

    /** @var array<string, RecipientLinksResource> by contact id, in the order added */
    public private(set) array $links = [];

    public private(set) RecipientListLinksResource $listLinks;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(CampaignRecipients $recipients): void
    {
        $this->recipients = $recipients;
        $removable = $this->intentLinks->shows('RemoveRecipient', $recipients->possibleIntents, User::PERSONAL_DATA);
        $running = $this->intentLinks->shows('RunCampaign', $recipients->possibleIntents, User::PERSONAL_DATA);

        foreach ($recipients->recipients as $contactId => $recipient) {
            $this->links[(string) $contactId] = $this->linksOf($recipients->campaignId, (string) $contactId, $recipient, $removable, $running && $recipient->pending);
        }

        $this->listLinks = new RecipientListLinksResource($this->routes->to('api_campaigns_recipients', ['id' => $recipients->campaignId]));
    }

    private function linksOf(string $campaignId, string $contactId, RecipientInSheet $recipient, bool $removable, bool $toHandle): RecipientLinksResource
    {
        $ids = ['id' => $campaignId, 'contactId' => $contactId];

        return new RecipientLinksResource(
            remove: $removable ? $this->routes->to('api_campaigns_remove_recipient', $ids) : null,
            noteExchange: $toHandle ? $this->routes->to('api_campaigns_note_exchange', $ids) : null,
            dismiss: $toHandle ? $this->routes->to('api_campaigns_dismiss_recipient', $ids) : null,
            exchange: $this->exchangeOf($recipient),
        );
    }

    private function exchangeOf(RecipientInSheet $recipient): ?LinkResource
    {
        return null === $recipient->exchangeId ? null : $this->routes->to('api_exchanges_show', ['id' => $recipient->exchangeId]);
    }
}
