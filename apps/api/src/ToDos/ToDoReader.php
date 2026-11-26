<?php

declare(strict_types=1);

namespace App\ToDos;

use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\ToDos\Resource\ToDoListLinksResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultMyToDos\ConsultMyToDos;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultOrganizationToDos\ConsultOrganizationToDos;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo\ConsultToDo;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDos\ConsultToDos;
use Prospecter\Core\Prospecting\Application\ToDo\PersonsOfToDos;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * The to-dos as the API shows them. Each read is one query of Prospecting, which names the to-dos' contacts and
 * holders, and a presenter built for the call (ADR 0085, 0217). A command returns nothing, so a controller calls this
 * reader after it to answer with the fresh resource.
 */
final readonly class ToDoReader
{
    public function __construct(
        private QueryHandler $consultToDo,
        private QueryHandler $consultToDos,
        private QueryHandler $consultOrganizationToDos,
        private QueryHandler $consultMyToDos,
        private PersonsOfToDos $personsOfToDos,
        private ToDoResourceFactory $factory,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $id): ToDoResource
    {
        $presenter = new ToDoPresenter($this->factory);
        $this->consultToDo->__invoke(new ConsultToDo($reader, $id), $presenter);

        return $presenter->resource;
    }

    public function ofContact(string $reader, string $personId): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, $this->listLinks('api_contacts_to_dos', 'api_contacts_note_to_do', ['id' => $personId]));
        $this->consultToDos->__invoke(new ConsultToDos($reader, $personId), $presenter);

        return $presenter->resource;
    }

    public function ofOrganization(string $reader, string $organizationId): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, $this->listLinks('api_organizations_to_dos', 'api_organizations_note_to_do', ['id' => $organizationId]));
        $this->consultOrganizationToDos->__invoke(new ConsultOrganizationToDos($reader, $organizationId), $presenter);

        return $presenter->resource;
    }

    public function mine(string $reader): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, new ToDoListLinksResource($this->links->to('api_me_to_dos')));
        $this->consultMyToDos->__invoke(new ConsultMyToDos($reader), $presenter);

        return $presenter->resource;
    }

    /**
     * @param array<string, string> $id
     */
    private function listLinks(string $route, string $noteRoute, array $id): ToDoListLinksResource
    {
        return new ToDoListLinksResource(
            $this->links->to($route, $id),
            $this->intentLinks->holds(User::PERSONAL_DATA) ? $this->links->to($noteRoute, $id) : null,
        );
    }

    /**
     * The to-dos an agenda shows, until the agenda's queries name them themselves.
     *
     * @param list<ToDoInList> $toDos
     *
     * @return list<ToDoResource>
     */
    public function resourcesOf(string $reader, array $toDos): array
    {
        $persons = $this->personsOfToDos->shownTo($reader, $toDos);

        return array_map(fn (ToDoInList $toDo): ToDoResource => $this->factory->of($toDo, $persons), $toDos);
    }
}
