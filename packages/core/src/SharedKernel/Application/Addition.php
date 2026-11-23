<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * A command that only adds something and contradicts nothing its client read: it creates a record, or attaches a
 * contact or a file. When it is refused because what it read changed meanwhile, it can run again on what it reads
 * then. Any other command is refused to its client, who reads the record again (ADR 0181).
 */
interface Addition
{
}
