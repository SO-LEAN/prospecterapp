<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RegisterAMember;

final readonly class RegisterAMember
{
    /**
     * @param list<string> $roles the roles the new member works with (`ROLE_PROSPECTOR`...)
     */
    public function __construct(
        public string $administratorId,
        public string $id,
        public string $profileId,
        public array $roles,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
