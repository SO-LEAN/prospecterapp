<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

use Prospecter\Core\PersonalData\Domain\Masking;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Reading;
use Prospecter\Core\PersonalData\Domain\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person as a reader may see them: in clear, or masked (no picture, M***r, m***@***), with what the reader
 * may change. Plain strings: a masked email is no email.
 */
final readonly class PersonResponse
{
    /**
     * @param list<PersonIntent> $possibleIntents
     */
    public function __construct(
        public PersonId $id,
        public bool $masked,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?File $picture,
        public array $possibleIntents,
    ) {
    }

    /**
     * @throws UnknownPerson when the reader may not even learn that the person exists
     */
    public static function of(Person $person, Reader $reader): self
    {
        $name = $person->name();
        $contact = $person->contactDetails();

        if (Reading::Masked === $person->readingFor($reader)) {
            return new self($person->id(), true, Masking::name($name->firstName), Masking::name($name->lastName), Masking::email($contact->email?->value), Masking::phoneNumber($contact->phoneNumber), $contact->language, null, $person->possibleIntentsOf($reader));
        }

        return new self($person->id(), false, $name->firstName, $name->lastName, $contact->email?->value, $contact->phoneNumber, $contact->language, $person->picture(), $person->possibleIntentsOf($reader));
    }
}
