<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * The published language of a member (ADR 0045, 0047): only what the other contexts need, it changes with them,
 * never with the model of Accounts. How long before their planned exchanges a member is reminded, none when they
 * turned their reminders off.
 */
final readonly class MemberResponse
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $personId,
        public bool $readsPersonalData,
        public bool $administers,
        public ?int $reminderMinutesBefore,
    ) {
    }
}
