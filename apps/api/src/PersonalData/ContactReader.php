<?php

namespace App\PersonalData;

use App\PersonalData\Presenter\ContactResourcePresenter;
use App\PersonalData\Resource\ContactResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * A contact as the API shows it: read by the query of the core, then presented. A controller calls it after a
 * command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ContactReader
{
    private RefusalTranslatingHandler $consultPerson;

    public function __construct(ConsultPersonHandler $consultPerson, private ContactResourcePresenter $presenter)
    {
        $this->consultPerson = new RefusalTranslatingHandler($consultPerson);
    }

    public function read(string $reader, string $id): ContactResource
    {
        return $this->presenter->present(($this->consultPerson)(new ConsultPerson($reader, $id)));
    }
}
