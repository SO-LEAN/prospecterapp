<?php

declare(strict_types=1);

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A calendar period, at its reader's offset: the start of its first day, and the start of the day after its last.
 */
final readonly class PeriodQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of the period\'s first day, included')]
        public ?\DateTimeImmutable $from = null,
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of the day after the period\'s last day, excluded. It is six weeks after `from` at most')]
        public ?\DateTimeImmutable $to = null,
    ) {
    }

    public function start(): \DateTimeImmutable
    {
        return $this->from ?? throw new \LogicException('A validated period has a start');
    }

    public function end(): \DateTimeImmutable
    {
        return $this->to ?? throw new \LogicException('A validated period has an end');
    }
}
