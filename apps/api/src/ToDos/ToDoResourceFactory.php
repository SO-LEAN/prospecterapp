<?php

namespace App\ToDos;

use App\Exchanges\Resource\AuthorResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use App\ToDos\Resource\ToDoContactResource;
use App\ToDos\Resource\ToDoLinksResource;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * A to-do as the API shows it, its holder and its contact named; each of its intentions needs the right to manage
 * personal data, its text possibly holding some.
 */
final readonly class ToDoResourceFactory
{
    /**
     * @var array<string, array{intent: string, route: string}>
     */
    private const array ROUTES = [
        'revise' => ['intent' => 'ReviseToDo', 'route' => 'api_to_dos_revise'],
        'tickOff' => ['intent' => 'TickOffToDo', 'route' => 'api_to_dos_tick_off'],
        'untick' => ['intent' => 'UntickToDo', 'route' => 'api_to_dos_untick'],
        'erase' => ['intent' => 'EraseToDo', 'route' => 'api_to_dos_erase'],
    ];

    public function __construct(private Links $links, private IntentLinks $intentLinks)
    {
    }

    /**
     * @param array<string, PersonCard> $persons        by person id, the contacts and the holders
     * @param array<string, string>     $personOfMember by member id, the person id of each holder
     */
    public function of(ToDoInList $toDo, array $persons, array $personOfMember): ToDoResource
    {
        $holder = $persons[$personOfMember[$toDo->heldBy] ?? ''] ?? null;

        return new ToDoResource(
            id: $toDo->id,
            text: $toDo->text,
            dueOn: $toDo->dueOn,
            dueAt: $toDo->dueAt,
            doneAt: $toDo->doneAt?->format(\DateTimeInterface::ATOM),
            heldBy: new AuthorResource(self::nameOf($holder), $holder?->email),
            contact: null === $toDo->personId ? null : new ToDoContactResource($toDo->personId, self::nameOf($persons[$toDo->personId] ?? null)),
            links: $this->linksOf($toDo),
        );
    }

    private static function nameOf(?PersonCard $person): PersonNameResource
    {
        return new PersonNameResource($person?->firstName, $person?->lastName);
    }

    private function linksOf(ToDoInList $toDo): ToDoLinksResource
    {
        $id = ['id' => $toDo->id];
        $granted = array_filter(self::ROUTES, fn (array $link): bool => $this->intentLinks->shows($link['intent'], $toDo->possibleIntents, User::PERSONAL_DATA));

        return new ToDoLinksResource(
            $this->links->to('api_to_dos_show', $id),
            ...array_map(fn (array $link): LinkResource => $this->links->to($link['route'], $id), $granted),
        );
    }
}
