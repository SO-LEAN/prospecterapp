<?php

declare(strict_types=1);

namespace App\ToDos;

use App\Contacts\PersonsOfAView;
use App\Contacts\PersonsShown;
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
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes the to-dos (ADR 0217). Prospecting reads them with their links, one query for each view. Accounts and
 * PersonalData name their contacts and holders, joined by the persons' ids. A command returns nothing, so a controller
 * calls this composition after it to answer with the fresh resource.
 */
final readonly class ToDoComposition
{
    public function __construct(
        private QueryHandler $consultToDo,
        private QueryHandler $consultToDos,
        private QueryHandler $consultOrganizationToDos,
        private QueryHandler $consultMyToDos,
        private ToDoResourceFactory $factory,
        private PersonsOfAView $personsOfAView,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $id): ToDoResource
    {
        $presenter = new ToDoPresenter($this->factory);
        $this->consultToDo->__invoke(new ConsultToDo($id), $presenter);

        return $this->factory->of($presenter->toDo, $presenter->links, $this->personsOf($reader, [$presenter->toDo]));
    }

    public function ofContact(string $reader, string $personId): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, $this->listLinks('api_contacts_to_dos', 'api_contacts_note_to_do', ['id' => $personId]));
        $this->consultToDos->__invoke(new ConsultToDos($reader, $personId), $presenter);

        return $this->listOf($reader, $presenter);
    }

    public function ofOrganization(string $reader, string $organizationId): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, $this->listLinks('api_organizations_to_dos', 'api_organizations_note_to_do', ['id' => $organizationId]));
        $this->consultOrganizationToDos->__invoke(new ConsultOrganizationToDos($organizationId), $presenter);

        return $this->listOf($reader, $presenter);
    }

    public function mine(string $reader): ToDoListResource
    {
        $presenter = new ToDoListPresenter($this->factory, new ToDoListLinksResource($this->links->to('api_me_to_dos')));
        $this->consultMyToDos->__invoke(new ConsultMyToDos($reader), $presenter);

        return $this->listOf($reader, $presenter);
    }

    /**
     * The to-dos' contacts and holders, as the reader reads them.
     *
     * @param list<ToDoInList> $toDos
     */
    private function personsOf(string $reader, array $toDos): PersonsShown
    {
        return $this->personsOfAView->shownTo(
            $reader,
            array_values(array_filter(array_map(static fn (ToDoInList $toDo): ?string => $toDo->personId, $toDos))),
            array_map(static fn (ToDoInList $toDo): string => $toDo->heldBy, $toDos),
        );
    }

    private function listOf(string $reader, ToDoListPresenter $presenter): ToDoListResource
    {
        $persons = $this->personsOf($reader, $presenter->toDos->toDos);

        return new ToDoListResource(
            array_map(fn (ToDoInList $toDo): ToDoResource => $this->factory->of($toDo, $presenter->links[$toDo->id], $persons), $presenter->toDos->toDos),
            $presenter->listLinks,
        );
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
}
