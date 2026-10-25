<?php

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\AttachmentLinksResource;
use App\Exchanges\Resource\AttachmentResource;
use App\Exchanges\Resource\AuthorResource;
use App\Exchanges\Resource\ExchangeDetailLinksResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Exchanges\Resource\StampResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;

/**
 * The detail of an exchange, with a link per intention its state offers and the signed-in member may carry out on it
 * and on its attachments (`IntentLinks`, ADR 0081): every prospector retouches its text and declares it conform
 * (ADR 0078), the rest needs the right to manage personal data. Each link names its intention, its route, and the role
 * its controller requires beyond reaching the exchange.
 */
final readonly class ExchangeDetailPresenter
{
    /**
     * @var array<string, array{intent: string, route: string, role: ?string}>
     */
    private const array ROUTES = [
        'correctExchange' => ['intent' => 'CorrectExchange', 'route' => 'api_exchanges_correct', 'role' => User::PERSONAL_DATA],
        'correctText' => ['intent' => 'CorrectText', 'route' => 'api_exchanges_correct_text', 'role' => null],
        'makeConform' => ['intent' => 'MakeConform', 'route' => 'api_exchanges_correct_text', 'role' => null],
        'eraseExchange' => ['intent' => 'EraseExchange', 'route' => 'api_exchanges_erase', 'role' => User::PERSONAL_DATA],
        'attachFile' => ['intent' => 'AttachFile', 'route' => 'api_exchanges_attach_file', 'role' => User::PERSONAL_DATA],
    ];

    public function __construct(private Links $links, private IntentLinks $intents)
    {
    }

    /**
     * @param array<string, PersonResponse> $persons        by person id
     * @param array<string, string>         $personOfMember the person id of each member, by member id
     */
    public function present(ExchangeDetailResponse $exchange, InterlocutorResource $interlocutor, array $persons, array $personOfMember): ExchangeDetailResource
    {
        $nameOf = static fn (string $person): PersonNameResource => new PersonNameResource($persons[$person]->firstName ?? null, $persons[$person]->lastName ?? null);
        $authorOf = static fn (string $member): AuthorResource => new AuthorResource($nameOf($personOfMember[$member]), $persons[$personOfMember[$member]]->email ?? null);

        return new ExchangeDetailResource(
            id: $exchange->id,
            interlocutor: $interlocutor,
            channel: $exchange->channel,
            direction: $exchange->direction,
            missed: $exchange->missed,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            text: $exchange->text,
            textFlagged: $exchange->textFlagged,
            textToMakeConform: $exchange->textToMakeConform,
            toMakeConform: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            noted: new StampResource($exchange->createdAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->createdBy)),
            corrected: null === $exchange->correctedAt || null === $exchange->correctedBy ? null : new StampResource($exchange->correctedAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->correctedBy)),
            madeConform: null === $exchange->madeConformAt || null === $exchange->madeConformBy ? null : new StampResource($exchange->madeConformAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->madeConformBy)),
            attachments: array_map(fn (AttachmentResponse $attachment): AttachmentResource => $this->presentAttachment($exchange, $attachment), $exchange->attachments),
            hiddenAttachments: $exchange->hiddenAttachments,
            links: $this->linksOf($exchange, $interlocutor),
        );
    }

    /**
     * Downloaded by whoever reads it, removed when the signed-in member may (`IntentLinks`).
     */
    private function presentAttachment(ExchangeDetailResponse $exchange, AttachmentResponse $attachment): AttachmentResource
    {
        $ids = ['id' => $exchange->id, 'attachmentId' => $attachment->id];

        return new AttachmentResource($attachment->id, $attachment->name, $attachment->size, $attachment->mediaType, $attachment->toMakeConform, new AttachmentLinksResource(
            $this->links->to('api_exchanges_download_attachment', $ids),
            $this->intents->shows('RemoveAttachment', $exchange->possibleIntents, User::PERSONAL_DATA) ? $this->links->to('api_exchanges_remove_attachment', $ids) : null,
        ));
    }

    private function linksOf(ExchangeDetailResponse $exchange, InterlocutorResource $interlocutor): ExchangeDetailLinksResource
    {
        $id = ['id' => $exchange->id];
        $granted = array_filter(self::ROUTES, fn (array $link): bool => $this->intents->shows($link['intent'], $exchange->possibleIntents, $link['role']));

        return new ExchangeDetailLinksResource(
            $this->links->to('api_exchanges_show', $id),
            $this->cardOf($interlocutor),
            ...array_map(fn (array $link): LinkResource => $this->links->to($link['route'], $id), $granted),
        );
    }

    /**
     * The card of the contact or of the organization itself; a forgotten contact has none any more.
     */
    private function cardOf(InterlocutorResource $interlocutor): ?LinkResource
    {
        if (InterlocutorResource::FORGOTTEN === $interlocutor->presence) {
            return null;
        }

        return $this->links->to(InterlocutorResource::PERSON === $interlocutor->kind ? 'api_contacts_show' : 'api_organizations_show', ['id' => $interlocutor->id]);
    }
}
