<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\AttachmentLinksResource;
use App\Exchanges\Resource\AttachmentResource;
use App\Exchanges\Resource\ExchangeDetailLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ConsultExchangeOutput;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Presents an exchange's detail. It gives one link for each intent that the exchange's state offers and that the
 * signed-in member can carry out, on the exchange and on its attachments (`IntentLinks`, ADR 0081):
 * - every prospector corrects its text and declares it redacted (ADR 0078);
 * - every prospector corrects a meeting's place or a video call's link (ADR 0114);
 * - the holder handles a planned exchange (ADR 0112). Just after a move, the holder can move it back while its former
 *   moment is still in the future (ADR 0135);
 * - the other intents need the right to manage personal data.
 *
 * Each link names its intent, its route, and the role its controller requires beyond reaching the exchange. Those who
 * read the campaigns get a link to the campaign the exchange was logged in, while the campaign exists (ADR 0152).
 * Those who manage personal data get a link that opens an imported exchange's message in Gmail (ADR 0174). The
 * presenter is built for one call. It keeps what Prospecting shows: the exchange, its links and the organization
 * itself when the exchange has no contact (ADR 0085, 0217).
 */
final class ExchangeDetailPresenter implements ConsultExchangeOutput
{
    /**
     * @var array<string, array{intent: string, route: string, role: ?string}>
     */
    private const array ROUTES = [
        'correctExchange' => ['intent' => 'CorrectExchange', 'route' => 'api_exchanges_correct', 'role' => User::PERSONAL_DATA],
        'correctText' => ['intent' => 'CorrectText', 'route' => 'api_exchanges_correct_text', 'role' => null],
        'correctVenue' => ['intent' => 'CorrectVenue', 'route' => 'api_exchanges_correct_venue', 'role' => null],
        'redact' => ['intent' => 'MakeConform', 'route' => 'api_exchanges_correct_text', 'role' => null],
        'eraseExchange' => ['intent' => 'EraseExchange', 'route' => 'api_exchanges_erase', 'role' => User::PERSONAL_DATA],
        'attachFile' => ['intent' => 'AttachFile', 'route' => 'api_exchanges_attach_file', 'role' => User::PERSONAL_DATA],
        'markDone' => ['intent' => 'MarkExchangeDone', 'route' => 'api_exchanges_mark_done', 'role' => null],
        'markMissed' => ['intent' => 'MarkExchangeMissed', 'route' => 'api_exchanges_mark_missed', 'role' => null],
        'move' => ['intent' => 'MoveExchange', 'route' => 'api_exchanges_move', 'role' => null],
        'moveBack' => ['intent' => 'MoveExchangeBack', 'route' => 'api_exchanges_move', 'role' => null],
        'cancel' => ['intent' => 'CancelExchange', 'route' => 'api_exchanges_cancel', 'role' => null],
    ];

    private const string GMAIL_MESSAGE = 'https://mail.google.com/mail/#all/%s';

    public private(set) ExchangeDetail $exchange;

    public private(set) ?OrganizationSheet $organizationItself = null;

    public private(set) ExchangeDetailLinksResource $links;

    /** @var list<AttachmentResource> */
    public private(set) array $attachments = [];

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself): void
    {
        $this->exchange = $exchange;
        $this->organizationItself = $organizationItself;
        $this->attachments = array_map(fn (AttachmentDetail $attachment): AttachmentResource => $this->presentAttachment($exchange, $attachment), $exchange->attachments);
        $this->links = $this->linksOf($exchange, $organizationItself);
    }

    /**
     * Whoever reads the attachment can download it. The signed-in member removes it when allowed (`IntentLinks`).
     */
    private function presentAttachment(ExchangeDetail $exchange, AttachmentDetail $attachment): AttachmentResource
    {
        $ids = ['id' => $exchange->id, 'attachmentId' => $attachment->id];

        return new AttachmentResource($attachment->id, $attachment->name, $attachment->size, $attachment->mediaType, $attachment->toMakeConform, new AttachmentLinksResource(
            $this->routes->to('api_exchanges_download_attachment', $ids),
            $this->intentLinks->shows('RemoveAttachment', $exchange->possibleIntents, User::PERSONAL_DATA) ? $this->routes->to('api_exchanges_remove_attachment', $ids) : null,
        ));
    }

    private function linksOf(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself): ExchangeDetailLinksResource
    {
        $id = ['id' => $exchange->id];
        $granted = array_filter(self::ROUTES, fn (array $link): bool => $this->intentLinks->shows($link['intent'], $exchange->possibleIntents, $link['role']));

        return new ExchangeDetailLinksResource(
            $this->routes->to('api_exchanges_show', $id),
            $this->cardOf($exchange, $organizationItself),
            ...array_map(fn (array $link): LinkResource => $this->routes->to($link['route'], $id), $granted),
            campaign: $this->campaignOf($exchange),
            openInMailbox: $this->openInMailboxOf($exchange),
        );
    }

    /**
     * Opens an imported exchange's message in Gmail. The link needs the message's id in the mailbox, and only those who
     * manage personal data get it (ADR 0174).
     */
    private function openInMailboxOf(ExchangeDetail $exchange): ?LinkResource
    {
        return null === $exchange->providerMessageId || !$this->intentLinks->holds(User::PERSONAL_DATA) ? null : new LinkResource(sprintf(self::GMAIL_MESSAGE, rawurlencode($exchange->providerMessageId)), 'GET');
    }

    /**
     * The campaign the exchange was logged in, while Campaigns still names it. Only those who read the campaigns get
     * it (ADR 0152).
     */
    private function campaignOf(ExchangeDetail $exchange): ?LinkResource
    {
        if (null === $exchange->campaignId || null === $exchange->campaignName || !$this->intentLinks->holds(User::PERSONAL_DATA)) {
            return null;
        }

        return $this->routes->to('api_campaigns_show', ['id' => $exchange->campaignId]);
    }

    /**
     * The card of the contact, or of the organization itself. A forgotten contact no longer has one, and neither has an
     * organization no longer known.
     */
    private function cardOf(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself): ?LinkResource
    {
        if (null === $exchange->personId) {
            return null === $organizationItself ? null : $this->routes->to('api_organizations_show', ['id' => $organizationItself->id]);
        }

        return InterlocutorResource::FORGOTTEN === $exchange->contactPresence ? null : $this->routes->to('api_contacts_show', ['id' => $exchange->personId]);
    }
}
