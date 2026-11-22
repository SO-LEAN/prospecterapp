<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The legal identity of a prospected organization, with the SIRET of its establishment: its spaces aside, what a SIRET
 * is, the core says.
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
