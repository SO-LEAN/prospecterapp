<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Policy\MemberPresentationPolicy;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The DynamoDB retriever in the scenarios: the persons the repository saved, read without their getters, those the
 * policy presents.
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
        $persons = array_filter(array_map(fn (PersonId $id): ?Person => $this->database->person($id->value), $ids), static fn (?Person $person): bool => null !== $person && MemberPresentationPolicy::presents($person->purpose()));

        return array_values(array_map(self::presentationOf(...), $persons));
    }

    private static function presentationOf(Person $person): MemberPresentation
    {
        $state = StateOf::the($person);

        return new MemberPresentation($person->id()->value, $state['name']->firstName, $state['name']->lastName, FileResponse::orNone($state['picture']));
    }
}
