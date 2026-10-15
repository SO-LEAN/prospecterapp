<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase;

abstract class AbstractUseCase implements \Stringable
{
    /**
     * @return list<string> the roles allowed to execute it, empty for everybody
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
        $shortName = preg_replace(['/^.*\\\\/', '/Impl$/'], '', static::class);

        return strtolower(trim((string) preg_replace('/([[:upper:]][[:lower:]]+)/', '$1 ', (string) $shortName)));
    }

    private function rolesAsWords(): string
    {
        $roles = array_map(static fn (string $role): string => preg_replace('/^role_/', '', strtolower($role)) ?? $role, $this->canBeExecutedBy());
        $lastRole = array_pop($roles) ?? 'anonymous';

        return [] === $roles ? $lastRole : sprintf('%s or %s', implode(', ', $roles), $lastRole);
    }
}
