<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactLinksResource;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Persistence\PersonRevisions;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\MailImport\ConsultMailCatchUpIntents\ConsultMailCatchUpIntents;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPerson;
use Prospecter\Core\Prospecting\Application\Contact\ConsultContactToAttach\ConsultContactToAttach;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes a contact's sheet (ADR 0217). PersonalData reads the person, with what the reader may do with them.
 * Prospecting says what the reader may do with the contact's timeline, and whether the contact waits for the reader to
 * attach them. Connectors says whether the e-mails exchanged with the contact may be caught up. Campaigns keeps their
 * tags. Each context answers its own query about the contact, and the composition puts the parts side by side. A
 * command returns nothing, so a controller reads the contact again after it to answer with the fresh resource.
 */
final readonly class ContactComposition
{
    public function __construct(
        private QueryHandler $consultPerson,
        private QueryHandler $consultTimelineIntents,
        private QueryHandler $consultContactToAttach,
        private QueryHandler $consultMailCatchUpIntents,
        private Links $links,
        private IntentLinks $intentLinks,
        private PersonRevisions $revisions,
    ) {
    }

    public function read(string $reader, string $id): ContactResource
    {
        $timeline = new ContactTimelinePresenter($this->links, $this->intentLinks, $id);
        $this->consultTimelineIntents->__invoke(new ConsultTimelineIntents($reader, $id), $timeline);
        $toAttach = new ContactToAttachPresenter($this->links, $this->intentLinks, $id);
        $this->consultContactToAttach->__invoke(new ConsultContactToAttach($reader, $id), $toAttach);
        $mail = new ContactMailPresenter($this->links, $this->intentLinks, $id);
        $this->consultMailCatchUpIntents->__invoke(ConsultMailCatchUpIntents::ofContact($reader, $id), $mail);
        $card = new ContactCardPresenter($this->links);
        $this->revisions->watch($id);
        $this->consultPerson->__invoke(new ConsultPerson($reader, $id), $card);
        $contact = $card->contact;
        $profile = $card->links;

        return new ContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            title: $contact->civility,
            jobTitle: $contact->jobTitle,
            contactDetails: PersonContactDetailsResource::of($contact),
            masked: $contact->masked,
            toAttach: $toAttach->toAttach,
            links: new ContactLinksResource(
                self: $this->links->to('api_contacts_show', ['id' => $id]),
                rename: $profile->rename,
                changeJobTitle: $profile->changeJobTitle,
                changeTitle: $profile->changeTitle,
                changeContactDetails: $profile->changeContactDetails,
                forget: $profile->forget,
                exchanges: $timeline->exchanges,
                toDos: $timeline->toDos,
                tags: $this->links->to('api_contacts_tags', ['id' => $id]),
                proposedOrganization: $toAttach->proposedOrganization,
                reimportGmail: $mail->links->reimportGmail,
            ),
            possibleDuplicates: $card->possibleDuplicates,
            createdByAnAI: $contact->createdByAnAI,
            revision: $this->revisions->lastRead($contact->id),
        );
    }
}
