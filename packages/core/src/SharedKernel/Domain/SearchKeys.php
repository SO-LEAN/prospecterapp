<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What an aggregate is found by (ADR 0071): the beginning of its name, or of its email; a person by the beginning
 * of their first name too (ADR 0149).
 */
final readonly class SearchKeys
{
    public ?SearchKey $name;
    public ?SearchKey $email;
    public ?SearchKey $firstName;

    public function __construct(?string $name, ?Email $email, ?string $firstName = null)
    {
        $this->name = SearchKey::of($name);
        $this->email = SearchKey::of($email?->value);
        $this->firstName = SearchKey::of($firstName);
    }

    /**
     * Without a search, everything is found.
     */
    public function areFoundBy(?SearchKey $search): bool
    {
        return null === $search || array_any(
            [$this->name, $this->email, $this->firstName],
            static fn (?SearchKey $key): bool => true === $key?->beginsWith($search),
        );
    }
}
