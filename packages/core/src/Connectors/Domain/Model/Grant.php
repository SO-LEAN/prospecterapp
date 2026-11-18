<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * What a provider granted for a code of authorization: the external account, its token, and the scopes it granted
 * among those Prospecter asked, the member being able to untick one at the consent.
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
