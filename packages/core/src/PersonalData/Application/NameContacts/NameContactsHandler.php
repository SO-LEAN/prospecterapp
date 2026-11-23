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
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class NameContactsHandler implements QueryHandler, NameContactsService
{
    public function __construct(
        private PersonCardRetriever $personCardRetriever,
        private Readers $readers,
    ) {
    }

    /**
     * An unknown reader reads nobody. The service refuses nothing.
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

        $output->present(array_map(self::responseOf(...), $cards));
    }

    private static function responseOf(PersonCard $card): ContactNameResponse
    {
        return new ContactNameResponse(
            $card->id,
            $card->firstName,
            $card->lastName,
            $card->civility,
            $card->jobTitle,
            $card->masked ? null : $card->email,
            $card->masked ? null : self::e164Of($card->phoneNumber),
        );
    }

    /**
     * A number kept before numbers were checked may be one that nobody can dial. Such a number counts as none.
     */
    private static function e164Of(?string $phoneNumber): ?string
    {
        try {
            return PhoneNumber::orNone($phoneNumber)?->value;
        } catch (InvalidPhoneNumber) {
            return null;
        }
    }
}
