<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\KeptTimelineIntents;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * A contact as the API shows it: read in PersonalData, what may be done with its timeline in Prospecting, then
 * presented to the presenter it builds for the call (ADR 0085). A controller calls it after a command, which
 * returns nothing, to answer with the fresh resource.
 */
final readonly class ContactReader
{
    public function __construct(
        private QueryHandler $consultPerson,
        private QueryHandler $consultTimelineIntents,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $id): ContactResource
    {
        $timeline = new KeptTimelineIntents();
        ($this->consultTimelineIntents)(new ConsultTimelineIntents($reader, $id), $timeline);
        $presenter = new ContactResourcePresenter($timeline->intents, $this->links, $this->intentLinks);
        ($this->consultPerson)(new ConsultPerson($reader, $id), $presenter);

        return $presenter->resource;
    }
}
