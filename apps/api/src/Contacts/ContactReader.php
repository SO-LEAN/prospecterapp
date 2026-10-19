<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * A contact as the API shows it: read in PersonalData, what may be done with its timeline in Prospecting, then
 * presented. A controller calls it after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ContactReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultPerson,
        private RefusalTranslatingHandler $consultTimelineIntents,
        private ContactResourcePresenter $presenter,
    ) {
    }

    public function read(string $reader, string $id): ContactResource
    {
        return $this->presenter->present(
            ($this->consultPerson)(new ConsultPerson($reader, $id)),
            ($this->consultTimelineIntents)(new ConsultTimelineIntents($reader, $id)),
        );
    }
}
