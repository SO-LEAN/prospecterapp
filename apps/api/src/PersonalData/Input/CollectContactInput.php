<?php

declare(strict_types=1);

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\Contact\CollectContact\CollectContact;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What the account knows about a person when it starts prospecting them, in the shape of ContactResource. It also
 * names the organizations they work for, at least one (ADR 0042).
 */
final readonly class CollectContactInput
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        #[Assert\Valid]
        public PersonNameInput $name = new PersonNameInput(),
        #[OA\Property(description: 'How to address the contact. It is x when unknown, the default.', enum: ['ms', 'mr', 'x'])]
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
     * A reader collects this person under this id. The command is the same from the screen and from an AI.
     */
    public function commandOf(string $readerId, string $id, \DateTimeImmutable $executedAt): CollectContact
    {
        return $this->command($readerId, $id, $executedAt, byAnAI: false);
    }

    /**
     * An AI collects this person, acting as the reader.
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
