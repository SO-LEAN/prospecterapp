<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\PersonalData\Domain\Rule\Masking;
use Prospecter\Core\PersonalData\Domain\Rule\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Rule\PersonReading;
use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Builds a person's card from the document that `DynamoPersonRepository` writes. The card shows what the reader may
 * read, as PersonalData's rules decide (ADR 0031, 0088). It is unmasked, or masked and without a picture. A
 * contact's name and title stay unmasked (ADR 0129).
 */
final class PersonCards
{
    /**
     * Whether the reader reaches the person of an item's untyped document.
     */
    public static function isReachableBy(Reader $reader, mixed $document): bool
    {
        return PersonReading::isReachableBy($reader, new AccountId(Typed::string(Typed::document($document)['accountId'])));
    }

    /**
     * Builds the card of a person the reader reaches, from an item's untyped document. The caller reads the possible
     * duplicates separately. A list of persons shows none.
     *
     * @param list<PossibleDuplicate> $possibleDuplicates
     */
    public static function of(mixed $document, Reader $reader, array $possibleDuplicates = []): PersonCard
    {
        $document = Typed::document($document);
        $id = new PersonId(Typed::string($document['id']));
        $purpose = Purpose::from(Typed::string($document['purpose']));
        $masked = Reading::Masked === PersonReading::by($reader, $id);
        $nameMasked = Reading::Masked === PersonReading::ofTheName($reader, $id, $purpose);
        $name = Typed::document($document['name']);
        $firstName = Typed::stringOrNull($name['firstName'] ?? null);
        $lastName = Typed::stringOrNull($name['lastName'] ?? null);
        $jobTitle = Typed::stringOrNull($document['jobTitle']);
        $contact = Documents::contactDetailsOf($document['contactDetails']);
        $references = PersonDocument::referencesOf($document);
        $possibleIntents = PersonPossibilities::given($reader, $id, new AccountId(Typed::string($document['accountId'])), $purpose);

        return new PersonCard(
            id: $id->value,
            masked: $masked,
            firstName: $nameMasked ? Masking::name($firstName) : $firstName,
            lastName: $nameMasked ? Masking::name($lastName) : $lastName,
            civility: PersonDocument::civilityOf($document)->value,
            jobTitle: $masked ? Masking::jobTitle($jobTitle) : $jobTitle,
            email: $masked ? Masking::email($contact->email?->value) : $contact->email?->value,
            phoneNumber: $masked ? Masking::phoneNumber($contact->phoneNumber) : $contact->phoneNumber?->international(),
            language: $contact->language,
            links: $masked ? array_map(ExternalLink::masked(...), Masking::references($references)) : ExternalLink::allOf($references),
            possibleDuplicates: $possibleDuplicates,
            picture: $masked ? null : FileResponse::orNone(Documents::fileOf($document['picture'])),
            possibleIntents: array_map(static fn (PersonIntent $intent): string => $intent->name, $possibleIntents),
            createdAt: Documents::momentOf(Typed::string($document['createdAt'])),
            modifiedAt: Documents::momentOf(Typed::string($document['modifiedAt'])),
            createdByAnAI: Origin::Ai === Documents::originOf($document),
        );
    }
}
