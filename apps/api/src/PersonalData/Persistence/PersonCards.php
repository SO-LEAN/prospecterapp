<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\PersonalData\Domain\Policy\Masking;
use Prospecter\Core\PersonalData\Domain\Policy\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Policy\PersonReading;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The card of a person from the document `DynamoPersonRepository` writes, as the reader may read it, asked to
 * the policies of PersonalData (ADR 0031, 0088): in clear, or masked and without picture.
 */
final class PersonCards
{
    /**
     * @param array<string, mixed> $document
     */
    public static function isReachableBy(Reader $reader, array $document): bool
    {
        return PersonReading::isReachableBy($reader, new AccountId($document['accountId']));
    }

    /**
     * For a person the reader reaches.
     *
     * @param array<string, mixed> $document
     */
    public static function of(array $document, Reader $reader): PersonCard
    {
        $id = new PersonId($document['id']);
        $masked = Reading::Masked === PersonReading::by($reader, $id);
        $name = $document['name'];
        $contact = Documents::contactDetailsOf($document['contactDetails']);
        $possibleIntents = PersonPossibilities::given($reader, $id, new AccountId($document['accountId']), Purpose::from($document['purpose']));

        return new PersonCard(
            id: $document['id'],
            masked: $masked,
            firstName: $masked ? Masking::name($name['firstName'] ?? null) : $name['firstName'] ?? null,
            lastName: $masked ? Masking::name($name['lastName'] ?? null) : $name['lastName'] ?? null,
            jobTitle: $masked ? Masking::jobTitle($document['jobTitle']) : $document['jobTitle'],
            email: $masked ? Masking::email($contact->email?->value) : $contact->email?->value,
            phoneNumber: $masked ? Masking::phoneNumber($contact->phoneNumber) : $contact->phoneNumber?->international(),
            language: $contact->language,
            picture: $masked ? null : FileResponse::orNone(Documents::fileOf($document['picture'])),
            possibleIntents: array_map(static fn (PersonIntent $intent): string => $intent->name, $possibleIntents),
            createdAt: Documents::momentOf($document['createdAt']),
            modifiedAt: Documents::momentOf($document['modifiedAt']),
        );
    }
}
