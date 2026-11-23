<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Contact\Email;

/**
 * What a provider granted for an authorization code. It holds the external account, its token, and the scopes granted
 * among those that Prospecter asked. The member may untick a scope on the consent screen.
 */
final readonly class Grant
{
    /** @var list<Scope> */
    public array $scopes;

    public function __construct(
        public Email $account,
        public RefreshToken $refreshToken,
        Scope ...$scopes,
    ) {
        $this->scopes = array_values($scopes);
    }

    public function grants(Scope $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
