<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

final readonly class LegalIdentity
{
    public function __construct(
        public ?string $corporateName = null,
        public ?string $form = null,
        public ?string $type = null,
    ) {
    }

    public function hasCorporateName(): bool
    {
        return null !== $this->corporateName && '' !== $this->corporateName;
    }

    public function fullName(): string
    {
        return trim(sprintf('%s %s', $this->corporateName, $this->form));
    }
}
