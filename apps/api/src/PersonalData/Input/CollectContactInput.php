<?php

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContact;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What is known of a person when the account starts prospecting them (the shape of ContactResource), and the
 * organizations they work for: at least one (ADR 0042).
 */
final readonly class CollectContactInput
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        #[Assert\Valid]
        public PersonNameInput $name = new PersonNameInput(),
        #[OA\Property(description: 'How one addresses the contact, x when unknown, the default', enum: ['ms', 'mr', 'x'])]
        public string $title = 'x',
        #[Assert\Length(max: 100)]
        public ?string $jobTitle = null,
        #[Assert\Valid]
        public PersonContactDetailsInput $contactDetails = new PersonContactDetailsInput(),
        #[Assert\All([new Assert\Uuid()])]
        public array $organizationIds = [],
    ) {
    }

    /**
     * The collecting of this person by a reader, under this id, the same from the screen and from an AI.
     */
    public function commandOf(string $readerId, string $id, \DateTimeImmutable $executedAt): CollectContact
    {
        return $this->command($readerId, $id, $executedAt, byAnAI: false);
    }

    /**
     * The same collecting, by an AI acting as the reader.
     */
    public function commandOfAnAI(string $readerId, string $id, \DateTimeImmutable $executedAt): CollectContact
    {
        return $this->command($readerId, $id, $executedAt, byAnAI: true);
    }

    private function command(string $readerId, string $id, \DateTimeImmutable $executedAt, bool $byAnAI): CollectContact
    {
        return new CollectContact(
            readerId: $readerId,
            id: $id,
            firstName: $this->name->firstName,
            lastName: $this->name->lastName,
            civility: $this->title,
            jobTitle: $this->jobTitle,
            email: $this->contactDetails->email,
            phoneNumber: $this->contactDetails->phoneNumber,
            language: $this->contactDetails->language,
            links: $this->contactDetails->links,
            organizationIds: $this->organizationIds,
            executedAt: $executedAt,
            byAnAI: $byAnAI,
        );
    }
}
