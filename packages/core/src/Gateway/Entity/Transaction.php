<?php

declare(strict_types=1);

namespace Prospecter\Core\Gateway\Entity;

interface Transaction
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;
}
