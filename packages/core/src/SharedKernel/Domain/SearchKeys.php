<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * What the search finds an aggregate by (ADR 0071): the beginning of its name or of its email. It also finds a
 * person by the beginning of their first name (ADR 0149).
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
     * An empty search finds everything.
     */
    public function areFoundBy(?SearchKey $search): bool
    {
        return null === $search || array_any(
            [$this->name, $this->email, $this->firstName],
            static fn (?SearchKey $key): bool => true === $key?->beginsWith($search),
        );
    }
}
