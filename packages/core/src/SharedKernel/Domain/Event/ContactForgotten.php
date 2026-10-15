<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact was erased: no organization may name it any more.
 */
final readonly class ContactForgotten
{
    public function __construct(
        public AccountId $accountId,
        public MemberId $forgottenBy,
        public PersonId $contact,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
