<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownToDo extends NotFound
{
    public static function identifiedBy(ToDoId $id): self
    {
        return new self(sprintf('To-do %s not found', $id));
    }
}
