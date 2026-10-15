<?php

namespace App\Api\Input;

use Prospecter\Core\Domain\Organization\LegalIdentity;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LegalIdentityInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $corporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $form = null,
        #[Assert\Length(max: 50)]
        public ?string $type = null,
    ) {
    }

    public function toLegalIdentity(): LegalIdentity
    {
        return new LegalIdentity($this->corporateName, $this->form, $this->type);
    }
}
