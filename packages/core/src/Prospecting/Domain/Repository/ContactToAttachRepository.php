<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;

interface ContactToAttachRepository
{
    /**
     * Returns null once the contact is attached or erased, or for a person who never was a contact to attach.
     */
    public function find(ContactId $contact): ?ContactToAttach;

    public function save(ContactToAttach $contactToAttach): void;

    public function remove(ContactToAttach $contactToAttach): void;
}
