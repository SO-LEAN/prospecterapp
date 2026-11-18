<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\SharedKernel\Domain\PersonId;

interface ContactToAttachRepository
{
    /**
     * None once attached or erased, or for a person who never was one.
     */
    public function find(PersonId $contact): ?ContactToAttach;

    public function save(ContactToAttach $contactToAttach): void;

    public function remove(ContactToAttach $contactToAttach): void;
}
