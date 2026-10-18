<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Masking;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Reading;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * A person as the reader may see them: in clear, or masked (then without picture).
 */
final readonly class PersonResponse
{
    /**
     * @param list<string> $possibleIntents names of what the reader may do: Rename, ChangeJobTitle, ChangeContactDetails, ChangePicture, Forget
     */
    private function __construct(
        public string $id,
        public bool $masked,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?FileResponse $picture,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }

    /**
     * @throws UnknownPerson when the reader may not even learn that the person exists
     */
    public static function of(Person $person, Reader $reader): self
    {
        return Reading::Masked === $person->readingFor($reader) ? self::masked($person, $reader) : self::inClear($person, $reader);
    }

    private static function inClear(Person $person, Reader $reader): self
    {
        $name = $person->name();
        $contact = $person->contactDetails();

        return new self($person->id()->value, false, $name->firstName, $name->lastName, $person->jobTitle(), $contact->email?->value, $contact->phoneNumber?->international(), $contact->language, FileResponse::orNone($person->picture()), self::intentsOf($person, $reader), $person->createdAt(), $person->modifiedAt());
    }

    private static function masked(Person $person, Reader $reader): self
    {
        $name = $person->name();
        $contact = $person->contactDetails();

        return new self($person->id()->value, true, Masking::name($name->firstName), Masking::name($name->lastName), Masking::jobTitle($person->jobTitle()), Masking::email($contact->email?->value), Masking::phoneNumber($contact->phoneNumber), $contact->language, null, self::intentsOf($person, $reader), $person->createdAt(), $person->modifiedAt());
    }

    /**
     * @return list<string>
     */
    private static function intentsOf(Person $person, Reader $reader): array
    {
        return array_map(static fn (PersonIntent $intent): string => $intent->name, $person->possibleIntentsOf($reader));
    }
}
