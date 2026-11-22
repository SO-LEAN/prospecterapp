<?php

declare(strict_types=1);

namespace App\ToDos\Resource;

use App\PersonalData\Resource\PersonNameResource;

/**
 * The contact a to-do is for, named by PersonalData.
 */
final readonly class ToDoContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
    ) {
    }
}
