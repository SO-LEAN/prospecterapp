<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

abstract class Base
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
