<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;

final readonly class InMemoryContactToAttachRepository implements ContactToAttachRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(ContactId $contact): ?ContactToAttach
    {
        return $this->database->contactToAttach((string) $contact);
    }

    public function save(ContactToAttach $contactToAttach): void
    {
        $this->database->storeContactToAttach($contactToAttach);
    }

    public function remove(ContactToAttach $contactToAttach): void
    {
        $this->database->forgetContactToAttach((string) $contactToAttach->id());
    }
}
