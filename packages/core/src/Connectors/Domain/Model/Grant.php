<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * What a provider granted for a code of authorization: the external account, its token, and whether it granted
 * everything Prospecter asked, the member being able to untick a scope at the consent.
 */
final readonly class Grant
{
    public function __construct(
        public Email $account,
        public RefreshToken $refreshToken,
        public bool $complete,
    ) {
    }
}
