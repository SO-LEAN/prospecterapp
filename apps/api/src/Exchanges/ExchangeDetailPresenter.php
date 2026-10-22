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
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;

/**
 * The detail of an exchange, with a link per intention the prospector may carry out on it and on its attachments.
 */
final readonly class ExchangeDetailPresenter
{
    private const array ROUTES = [
        'CorrectExchange' => ['correctExchange', 'api_exchanges_correct'],
        'CorrectText' => ['correctText', 'api_exchanges_correct_text'],
        'EraseExchange' => ['eraseExchange', 'api_exchanges_erase'],
        'AttachFile' => ['attachFile', 'api_exchanges_attach_file'],
    ];

    public function __construct(private Links $links)
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
            organizationIds: $exchange->organizationIds,
            noted: new StampResource($exchange->createdAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->createdBy)),
            corrected: null === $exchange->correctedAt || null === $exchange->correctedBy ? null : new StampResource($exchange->correctedAt->format(\DateTimeInterface::ATOM), $authorOf($exchange->correctedBy)),
            attachments: array_map(fn (AttachmentResponse $attachment): AttachmentResource => $this->presentAttachment($exchange, $attachment), $exchange->attachments),
            hiddenAttachments: $exchange->hiddenAttachments,
            links: $this->linksOf($exchange, $interlocutor),
        );
    }

    /**
     * Downloaded by whoever reads the exchange, removed when the prospector may.
     */
    private function presentAttachment(ExchangeDetailResponse $exchange, AttachmentResponse $attachment): AttachmentResource
    {
        $ids = ['id' => $exchange->id, 'attachmentId' => $attachment->id];

        return new AttachmentResource($attachment->id, $attachment->name, $attachment->size, $attachment->mediaType, new AttachmentLinksResource(
            $this->links->to('api_exchanges_download_attachment', $ids),
            in_array('RemoveAttachment', $exchange->possibleIntents, true) ? $this->links->to('api_exchanges_remove_attachment', $ids) : null,
        ));
    }

    private function linksOf(ExchangeDetailResponse $exchange, InterlocutorResource $interlocutor): ExchangeDetailLinksResource
    {
        $id = ['id' => $exchange->id];
        $possible = [];

        foreach ($exchange->possibleIntents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->links->to($route, $id);
            }
        }

        return new ExchangeDetailLinksResource(
            $this->links->to('api_exchanges_show', $id),
            $this->links->to(InterlocutorResource::PERSON === $interlocutor->kind ? 'api_contacts_show' : 'api_organizations_show', ['id' => $interlocutor->id]),
            ...$possible,
        );
    }
}
