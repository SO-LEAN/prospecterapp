<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

/**
 * An intention of a user, executed with execute(request, presenter). Who may run it is decided by the
 * delivery (roles) and by the aggregate it changes (ownership, state).
 */
abstract class UseCase implements \Stringable
{
    public function __toString(): string
    {
        $shortName = (string) preg_replace('/^.*\\\\/', '', static::class);

        return sprintf('I want to %s', strtolower(trim((string) preg_replace('/([[:upper:]][[:lower:]]+)/', '$1 ', $shortName))));
    }
}
