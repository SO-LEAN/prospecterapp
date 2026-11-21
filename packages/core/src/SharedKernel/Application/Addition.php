<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * A command that only adds, contradicting nothing its client read: a record it creates, a contact or a file it
 * attaches. Refused because what it read changed meanwhile, it may be run again from what it reads then; any other
 * command is refused to its client, who reads the record again (ADR 0181).
 */
interface Addition
{
}
