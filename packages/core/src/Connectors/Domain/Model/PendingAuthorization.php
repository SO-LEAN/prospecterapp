<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * An authorization a member asked a provider for, waiting for its answer: the `state` the provider gives back with
 * it, and the verifier of the code it will give (PKCE), bound to the member for ten minutes (ADR 0120).
 */
final readonly class PendingAuthorization
{
    private const string LIFETIME = '+10 minutes';

    private function __construct(
        public string $state,
        public MemberId $member,
        public Provider $provider,
        public string $codeVerifier,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public static function askedBy(MemberId $member, Provider $provider, string $state, string $codeVerifier, \DateTimeImmutable $at): self
    {
        return new self($state, $member, $provider, $codeVerifier, $at->modify(self::LIFETIME));
    }

    public function awaits(MemberId $member, \DateTimeImmutable $at): bool
    {
        return $this->member->equals($member) && $at < $this->expiresAt;
    }
}
