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
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;

/**
 * The detail of an exchange, with a link per intention the prospector may carry out on it and on its attachments.
 */
final readonly class ExchangeDetailPresenter
{
    private const array ROUTES = [
        'correctExchange' => [ExchangeVoter::CORRECT_EXCHANGE, 'api_exchanges_correct'],
        'correctText' => [ExchangeVoter::CORRECT_TEXT, 'api_exchanges_correct_text'],
        'makeConform' => [ExchangeVoter::MAKE_CONFORM, 'api_exchanges_correct_text'],
        'eraseExchange' => [ExchangeVoter::ERASE_EXCHANGE, 'api_exchanges_erase'],
        'attachFile' => [ExchangeVoter::ATTACH_FILE, 'api_exchanges_attach_file'],
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
            $this->intents->shows(ExchangeVoter::REMOVE_ATTACHMENT, $exchange, $exchange->possibleIntents) ? $this->links->to('api_exchanges_remove_attachment', $ids) : null,
        ));
    }

    private function linksOf(ExchangeDetailResponse $exchange, InterlocutorResource $interlocutor): ExchangeDetailLinksResource
    {
        $id = ['id' => $exchange->id];
        $granted = array_filter(self::ROUTES, fn (array $route): bool => $this->intents->shows($route[0], $exchange, $exchange->possibleIntents));

        return new ExchangeDetailLinksResource(
            $this->links->to('api_exchanges_show', $id),
            $this->cardOf($interlocutor),
            ...array_map(fn (array $route): LinkResource => $this->links->to($route[1], $id), $granted),
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
