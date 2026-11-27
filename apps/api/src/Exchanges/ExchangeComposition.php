<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\PersonsOfAView;
use App\Contacts\PersonsShown;
use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Exchanges\Resource\StampResource;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Prospecting\Resource\CampaignOfExchangeResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\KeptExchange;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes an exchange's detail (ADR 0217). Prospecting's query reads the exchange, its links, and the organization
 * itself when the exchange has no contact. Accounts and PersonalData name its contact and the members who logged,
 * corrected and redacted it, joined by their ids. A command returns nothing, so a controller calls this composition
 * after it to answer with the fresh resource. After a move, the controller passes the moment the exchange was moved
 * from, so that the resource offers to move it back there.
 */
final readonly class ExchangeComposition
{
    public function __construct(
        private QueryHandler $consultExchange,
        private PersonsOfAView $personsOfAView,
        private Links $links,
        private IntentLinks $intents,
        private ClockInterface $clock,
        private ExchangeRevisions $revisions,
    ) {
    }

    public function read(string $reader, string $id, ?\DateTimeImmutable $movedFrom = null): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->links, $this->intents);
        $this->revisions->watch($id);
        $this->consultExchange->__invoke(new ConsultExchange(prospectorId: $reader, id: $id, asOf: $this->clock->now(), movedFrom: $movedFrom), $presenter);

        return $this->resourceOf($reader, $presenter);
    }

    /**
     * Composes an exchange already read for its voter, without reading it again.
     */
    public function ofKept(string $reader, KeptExchange $exchange): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->links, $this->intents);
        $presenter->present($exchange->exchange, $exchange->organizationItself);

        return $this->resourceOf($reader, $presenter);
    }

    private function resourceOf(string $reader, ExchangeDetailPresenter $presenter): ExchangeDetailResource
    {
        $exchange = $presenter->exchange;
        $persons = $this->personsOfAView->shownTo($reader, array_filter([$exchange->personId]), array_values(array_filter([$exchange->createdBy, $exchange->correctedBy, $exchange->madeConformBy])));

        return new ExchangeDetailResource(
            id: $exchange->id,
            interlocutor: self::interlocutorOf($exchange, $presenter->organizationItself, $persons),
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
            phoneNumber: self::numberToCall($exchange, $presenter->organizationItself, $persons),
            textFlagged: $exchange->textFlagged,
            textToRedact: $exchange->textToMakeConform,
            toRedact: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            noted: new StampResource($exchange->createdAt->format(\DateTimeInterface::ATOM), $persons->author($exchange->createdBy)),
            corrected: null === $exchange->correctedAt || null === $exchange->correctedBy ? null : new StampResource($exchange->correctedAt->format(\DateTimeInterface::ATOM), $persons->author($exchange->correctedBy)),
            redacted: null === $exchange->madeConformAt || null === $exchange->madeConformBy ? null : new StampResource($exchange->madeConformAt->format(\DateTimeInterface::ATOM), $persons->author($exchange->madeConformBy)),
            attachments: $presenter->attachments,
            hiddenAttachments: $exchange->hiddenAttachments,
            campaign: null === $exchange->campaignId ? null : new CampaignOfExchangeResource($exchange->campaignId, $exchange->campaignName),
            bookedByLink: $exchange->bookedByLink,
            importedFrom: $exchange->importedFrom,
            createdByAnAI: $exchange->createdByAnAI,
            revision: $this->revisions->lastRead($exchange->id),
            links: $presenter->links,
        );
    }

    /**
     * The organization itself when the exchange has no contact, unnamed once it is no longer known. Its contact
     * otherwise.
     */
    private static function interlocutorOf(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself, PersonsShown $persons): InterlocutorResource
    {
        if (null === $exchange->personId) {
            return null === $organizationItself ? InterlocutorResource::ofAnOrganizationNoLongerKnown($exchange->organizationIds[0]) : InterlocutorResource::ofTheOrganization($organizationItself);
        }

        return InterlocutorResource::ofPerson($exchange->personId, $persons->contact($exchange->personId), $exchange->contactPresence);
    }

    /**
     * The organization's own number, or the contact's number read in clear. A masked number calls nobody.
     */
    private static function numberToCall(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself, PersonsShown $persons): ?string
    {
        if (null !== $organizationItself) {
            return $organizationItself->phoneNumber;
        }

        $contact = $persons->contact($exchange->personId ?? '');

        return null === $contact || $contact->masked ? null : $contact->phoneNumber;
    }
}
