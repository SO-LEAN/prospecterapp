<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * A member in the published language (ADR 0045, 0047). It holds only what the other contexts need. It changes with
 * their needs, never with Accounts' model. `reminderMinutesBefore` says how long before their planned exchanges the
 * member is reminded. It is null when they turned their reminders off. `accountName` is their account's corporate
 * name, which a booking page shows (ADR 0153).
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
        public ?string $accountName,
    ) {
    }
}
