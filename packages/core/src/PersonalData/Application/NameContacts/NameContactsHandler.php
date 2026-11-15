<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\NameContacts;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\ContactNameResponse;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsOutput;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsService;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NameContactsHandler implements QueryHandler, NameContactsService
{
    public function __construct(
        private PersonCardRetriever $personCardRetriever,
        private Readers $readers,
    ) {
    }

    /**
     * A reader nobody knows reads nobody: the service refuses nothing.
     */
    public function __invoke(NameContacts $query, NameContactsOutput $output): void
    {
        try {
            $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        } catch (UnknownReader) {
            $output->present([]);

            return;
        }

        $cards = $this->personCardRetriever->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds), $reader);

        $output->present(array_map(static fn (PersonCard $card): ContactNameResponse => new ContactNameResponse($card->id, $card->firstName, $card->lastName), $cards));
    }
}
