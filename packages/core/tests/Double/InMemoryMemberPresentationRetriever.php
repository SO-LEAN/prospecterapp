<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\File\File;

/**
 * Stands for the DynamoDB retriever in the scenarios. It reads the persons that the repository saved, without
 * their getters.
 */
final readonly class InMemoryMemberPresentationRetriever implements MemberPresentationRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids): array
    {
        $persons = array_filter(array_map(fn (PersonId $id): ?Person => $this->database->person($id->value), $ids));

        return array_values(array_map(self::presentationOf(...), $persons));
    }

    private static function presentationOf(Person $person): MemberPresentation
    {
        $state = StateOf::the($person);

        return new MemberPresentation($person->id()->value, $state->object('name', PersonName::class)->firstName, $state->object('name', PersonName::class)->lastName, FileResponse::orNone($state->objectOrNull('picture', File::class)), $person->purpose()->value);
    }
}
