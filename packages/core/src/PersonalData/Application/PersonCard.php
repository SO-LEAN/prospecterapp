<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * A person as the reader may see them, unmasked or masked. A masked person has no picture. A contact's name and title
 * are always unmasked. The card also tells whether an AI collected the person.
 */
final readonly class PersonCard
{
    /**
     * @param list<ExternalLink>      $links              their links to outside tools, or a single link of stars when masked
     * @param list<PossibleDuplicate> $possibleDuplicates the contacts they resemble, given only to a reader with the right to manage personal data
     * @param list<string>            $possibleIntents    the names of what the reader may do: Rename, ChangeJobTitle, ChangeCivility, ChangeContactDetails, ChangePicture, Forget
     */
    public function __construct(
        public string $id,
        public bool $masked,
        public ?string $firstName,
        public ?string $lastName,
        public string $civility,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public array $links,
        public array $possibleDuplicates,
        public ?FileResponse $picture,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
        public bool $createdByAnAI = false,
    ) {
    }
}
