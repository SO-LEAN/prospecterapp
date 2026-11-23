<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * Who acts, in which account, and when. The member acts by hand or through an AI acting for them (ADR 0185). An
 * aggregate's factories take it as their argument object (ADR 0191).
 */
final readonly class Gesture
{
    public function __construct(
        public AccountId $accountId,
        public MemberId $by,
        public \DateTimeImmutable $at,
        public Origin $origin = Origin::Hand,
    ) {
    }
}
