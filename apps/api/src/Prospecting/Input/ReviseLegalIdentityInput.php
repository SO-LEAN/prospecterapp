<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * A prospected organization's legal identity, with its establishment's SIRET. Apart from its spaces, the core decides
 * what a valid SIRET is.
 */
final readonly class ReviseLegalIdentityInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $corporateName = null,
        #[Assert\Length(max: 50)]
        public ?string $form = null,
        #[Assert\Length(max: 50)]
        public ?string $type = null,
        #[Assert\Length(max: 20)]
        public ?string $siret = null,
    ) {
    }
}
