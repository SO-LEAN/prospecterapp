<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\AttachmentLinksResource;
use App\Exchanges\Resource\AttachmentResource;
use App\Exchanges\Resource\AuthorResource;
use App\Exchanges\Resource\ExchangeDetailLinksResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Exchanges\Resource\StampResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Prospecting\Resource\CampaignOfExchangeResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchangeOutput;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;

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
 * presenter is built for one call with its participants, and keeps the resource (ADR 0085).
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

    public private(set) ExchangeDetailResource $resource;

    public function __construct(private readonly ExchangeParticipants $participants, private readonly Links $links, private readonly IntentLinks $intentLinks, private readonly ExchangeRevisions $revisions)
    {
    }

    public function present(ExchangeDetail $exchange): void
    {
        $persons = $this->participants->persons;
        $personOfMember = $this->participants->personOfMember;
        $nameOf = static fn (string $person): PersonNameResource => new PersonNameResource($persons[$person]->firstName ?? null, $persons[$person]->lastName ?? null);
        $authorOf = static fn (string $member): AuthorResource => new AuthorResource($nameOf($personOfMember[$member]), $persons[$personOfMember[$member]]->email ?? null);

        $this->resource = new ExchangeDetailResource(
            id: $exchange->id,
            interlocutor: $this->participants->interlocutor,
            channel: $exchange->channel,
            direction: $exchange->direction,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            status: $exchange->status,
            canceller: $exchange->canceller,
            durationInMinutes: $exchange->durationInMinutes,
            text: $exchange->text,
            place: $exchange->place,
            link: $exchange->link,
            linkAwaited: $exchange->linkAwaited,
            phoneNumber: $this->participants->interlocutorPhoneNumber,
            textFlagged: $exchange->textFlagged,
            textToRedact: $exchange->textToMakeConform,
            toRedact: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            noted: new StampResource($exchange->createdAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->createdBy)),
            corrected: null === $exchange->correctedAt || null === $exchange->correctedBy ? null : new StampResource($exchange->correctedAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->correctedBy)),
            redacted: null === $exchange->madeConformAt || null === $exchange->madeConformBy ? null : new StampResource($exchange->madeConformAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->madeConformBy)),
            attachments: array_map(fn (AttachmentDetail $attachment): AttachmentResource => $this->presentAttachment($exchange, $attachment), $exchange->attachments),
            hiddenAttachments: $exchange->hiddenAttachments,
            campaign: null === $exchange->campaignId ? null : new CampaignOfExchangeResource($exchange->campaignId, $exchange->campaignName),
            bookedByLink: $exchange->bookedByLink,
            importedFrom: $exchange->importedFrom,
            createdByAnAI: $exchange->createdByAnAI,
            revision: $this->revisions->lastRead($exchange->id),
            links: $this->linksOf($exchange),
        );
    }

    /**
     * Whoever reads the attachment can download it. The signed-in member removes it when allowed (`IntentLinks`).
     */
    private function presentAttachment(ExchangeDetail $exchange, AttachmentDetail $attachment): AttachmentResource
    {
        $ids = ['id' => $exchange->id, 'attachmentId' => $attachment->id];

        return new AttachmentResource($attachment->id, $attachment->name, $attachment->size, $attachment->mediaType, $attachment->toMakeConform, new AttachmentLinksResource(
            $this->links->to('api_exchanges_download_attachment', $ids),
            $this->intentLinks->shows('RemoveAttachment', $exchange->possibleIntents, User::PERSONAL_DATA) ? $this->links->to('api_exchanges_remove_attachment', $ids) : null,
        ));
    }

    private function linksOf(ExchangeDetail $exchange): ExchangeDetailLinksResource
    {
        $id = ['id' => $exchange->id];
        $granted = array_filter(self::ROUTES, fn (array $link): bool => $this->intentLinks->shows($link['intent'], $exchange->possibleIntents, $link['role']));

        return new ExchangeDetailLinksResource(
            $this->links->to('api_exchanges_show', $id),
            $this->cardOf($this->participants->interlocutor),
            ...array_map(fn (array $link): LinkResource => $this->links->to($link['route'], $id), $granted),
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

        return $this->links->to('api_campaigns_show', ['id' => $exchange->campaignId]);
    }

    /**
     * The card of the contact, or of the organization itself. A forgotten contact no longer has one.
     */
    private function cardOf(InterlocutorResource $interlocutor): ?LinkResource
    {
        if (InterlocutorResource::FORGOTTEN === $interlocutor->presence) {
            return null;
        }

        return $this->links->to(InterlocutorResource::PERSON === $interlocutor->kind ? 'api_contacts_show' : 'api_organizations_show', ['id' => $interlocutor->id]);
    }
}
