<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * A person as the reader may see them: in clear, or masked (then without picture), the name and the civility of a
 * contact in clear.
 */
final readonly class PersonCard
{
    /**
     * @param list<string> $possibleIntents names of what the reader may do: Rename, ChangeJobTitle, ChangeCivility, ChangeContactDetails, ChangePicture, Forget
     */
    public function __construct(
        public string $id,
        public bool $masked,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $civility,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?string $linkedInProfile,
        public ?FileResponse $picture,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }
}
