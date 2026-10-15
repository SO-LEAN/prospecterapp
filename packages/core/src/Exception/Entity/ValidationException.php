<?php

declare(strict_types=1);

namespace Prospecter\Core\Exception\Entity;

final class ValidationException extends \RuntimeException
{
    public function __construct(string $message, private readonly string $field)
    {
        parent::__construct($message);
    }

    public function getField(): string
    {
        return $this->field;
    }
}
