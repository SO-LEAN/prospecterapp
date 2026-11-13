<?php

namespace App\ToDos;

use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\ToDos\Resource\ToDoListLinksResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\Prospecting\Application\ConsultMyToDos\ConsultMyToDos;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationToDos\ConsultOrganizationToDos;
use Prospecter\Core\Prospecting\Application\ConsultToDo\ConsultToDo;
use Prospecter\Core\Prospecting\Application\ConsultToDo\KeptToDo;
use Prospecter\Core\Prospecting\Application\ConsultToDos\ConsultToDos;
use Prospecter\Core\Prospecting\Application\KeptToDoList;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The to-dos as the API shows them: read in Prospecting, their holders found in Accounts, their holders and their
 * contacts named by PersonalData, each asked once for all of them.
 */
final readonly class ToDoReader
{
    public function __construct(
        private QueryHandler $consultToDo,
        private QueryHandler $consultToDos,
        private QueryHandler $consultOrganizationToDos,
        private QueryHandler $consultMyToDos,
        private ConsultMembersService $consultMembersService,
        private QueryHandler $consultPersons,
        private ToDoResourceFactory $factory,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $id): ToDoResource
    {
        $kept = new KeptToDo();
        ($this->consultToDo)(new ConsultToDo($id), $kept);

        return $this->resourcesOf($reader, [$kept->toDo])[0];
    }

    public function ofContact(string $reader, string $personId): ToDoListResource
    {
        $kept = new KeptToDoList();
        ($this->consultToDos)(new ConsultToDos($reader, $personId), $kept);

        return $this->listOf($reader, $kept, 'api_contacts_to_dos', 'api_contacts_note_to_do', ['id' => $personId]);
    }

    public function ofOrganization(string $reader, string $organizationId): ToDoListResource
    {
        $kept = new KeptToDoList();
        ($this->consultOrganizationToDos)(new ConsultOrganizationToDos($organizationId), $kept);

        return $this->listOf($reader, $kept, 'api_organizations_to_dos', 'api_organizations_note_to_do', ['id' => $organizationId]);
    }

    public function mine(string $reader): ToDoListResource
    {
        $kept = new KeptToDoList();
        ($this->consultMyToDos)(new ConsultMyToDos($reader), $kept);

        return new ToDoListResource($this->resourcesOf($reader, $kept->toDos->toDos), new ToDoListLinksResource($this->links->to('api_me_to_dos')));
    }

    /**
     * @param array<string, string> $id
     */
    private function listOf(string $reader, KeptToDoList $kept, string $route, string $noteRoute, array $id): ToDoListResource
    {
        return new ToDoListResource($this->resourcesOf($reader, $kept->toDos->toDos), new ToDoListLinksResource(
            $this->links->to($route, $id),
            $this->intentLinks->holds(User::PERSONAL_DATA) ? $this->links->to($noteRoute, $id) : null,
        ));
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return list<ToDoResource>
     */
    private function resourcesOf(string $reader, array $toDos): array
    {
        $personOfMember = $this->personsOfMembers(array_map(static fn (ToDoInList $toDo): string => $toDo->heldBy, $toDos));
        $persons = new KeptPersons();
        $personIds = [...array_values($personOfMember), ...array_filter(array_map(static fn (ToDoInList $toDo): ?string => $toDo->personId, $toDos))];
        ($this->consultPersons)(new ConsultPersons($reader, array_values(array_unique($personIds))), $persons);

        return array_map(fn (ToDoInList $toDo): ToDoResource => $this->factory->of($toDo, $persons->persons, $personOfMember), $toDos);
    }

    /**
     * @param list<string> $members
     *
     * @return array<string, string> the person id of each member found, by member id
     */
    private function personsOfMembers(array $members): array
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique($members))), $kept);

        return array_map(static fn (MemberResponse $member): string => $member->personId, $kept->members);
    }
}
