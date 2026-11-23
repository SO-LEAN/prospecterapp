<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * An authorization that a member asked a provider for, waiting for the provider's answer. It holds what the member
 * asked, and it is bound to the member for ten minutes (ADR 0120, 0154).
 */
final readonly class PendingAuthorization
{
    private const string LIFETIME = '+10 minutes';

    /**
     * @param list<Scope> $scopes
     */
    private function __construct(
        public string $state,
        public MemberId $member,
        public Provider $provider,
        public string $codeVerifier,
        public \DateTimeImmutable $expiresAt,
        public array $scopes,
    ) {
    }

    public static function askedBy(MemberId $member, AuthorizationRequest $request, \DateTimeImmutable $at): self
    {
        return new self($request->state, $member, $request->provider, $request->codeVerifier, $at->modify(self::LIFETIME), $request->scopes);
    }

    public function asks(Scope $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function awaits(MemberId $member, \DateTimeImmutable $at): bool
    {
        return $this->member->equals($member) && $at < $this->expiresAt;
    }
}
