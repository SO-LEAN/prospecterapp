<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactResource;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents\ConsultMailCatchUpIntents;
use Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents\KeptMailCatchUpIntents;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\Prospecting\Application\ConsultContactToAttach\ConsultContactToAttach;
use Prospecter\Core\Prospecting\Application\ConsultContactToAttach\KeptContactToAttach;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\KeptTimelineIntents;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * A contact as the API shows it: read in PersonalData, what may be done with its timeline in Prospecting, whether
 * they wait for the reader to attach them, and whether the e-mails exchanged with them may be caught up in Connectors,
 * then presented to the presenter it builds for the call (ADR 0085). A controller calls it after a command, which
 * returns nothing, to answer with the fresh resource.
 */
final readonly class ContactReader
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
        $timeline = new KeptTimelineIntents();
        $this->consultTimelineIntents->__invoke(new ConsultTimelineIntents($reader, $id), $timeline);
        $toAttach = new KeptContactToAttach();
        $this->consultContactToAttach->__invoke(new ConsultContactToAttach($reader, $id), $toAttach);
        $catchUp = new KeptMailCatchUpIntents();
        $this->consultMailCatchUpIntents->__invoke(ConsultMailCatchUpIntents::ofContact($reader, $id), $catchUp);
        $presenter = new ContactResourcePresenter($timeline->intents, $toAttach->contactToAttach, $catchUp->possibleIntents, $this->links, $this->intentLinks, $this->revisions);
        $this->revisions->watch($id);
        $this->consultPerson->__invoke(new ConsultPerson($reader, $id), $presenter);

        return $presenter->resource;
    }
}
