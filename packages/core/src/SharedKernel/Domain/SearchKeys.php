<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What an aggregate is found by (ADR 0071): the beginning of its name, or of its email.
 */
final readonly class SearchKeys
{
    public ?SearchKey $name;
    public ?SearchKey $email;

    public function __construct(?string $name, ?Email $email)
    {
        $this->name = SearchKey::of($name);
        $this->email = SearchKey::of($email?->value);
    }

    /**
     * Without a search, everything is found.
     */
    public function areFoundBy(?SearchKey $search): bool
    {
        return null === $search || true === $this->name?->beginsWith($search) || true === $this->email?->beginsWith($search);
    }
}
