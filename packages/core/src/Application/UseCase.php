<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

use Prospecter\Core\Domain\User\Role;

/**
 * An intention of a user, executed with execute(request, presenter, consumer).
 */
abstract class UseCase implements \Stringable
{
    /**
     * @return list<Role> the roles allowed to execute it, empty for everybody
     */
    public function canBeExecutedBy(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return sprintf('As %s, I want to %s', $this->rolesAsWords(), $this->nameAsWords());
    }

    private function nameAsWords(): string
    {
        $shortName = (string) preg_replace('/^.*\\\\/', '', static::class);

        return strtolower(trim((string) preg_replace('/([[:upper:]][[:lower:]]+)/', '$1 ', $shortName)));
    }

    private function rolesAsWords(): string
    {
        $roles = array_map(static fn (Role $role): string => strtolower($role->name), $this->canBeExecutedBy());
        $lastRole = array_pop($roles) ?? 'anonymous';

        return [] === $roles ? $lastRole : sprintf('%s or %s', implode(', ', $roles), $lastRole);
    }
}
