<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

final readonly class ExchangeId extends Uuid
{
    /**
     * The exchange of an e-mail imported with one correspondent of an account is always the same: no client gives the
     * identity of what a reaction imports, and an e-mail imported again finds the exchange it made (ADR 0154).
     */
    public static function ofImportedEmail(AccountId $account, string $email, PersonId|OrganizationId $correspondent): self
    {
        return self::namedBy(sprintf('%s#%s#%s', $account->value, $email, $correspondent->value));
    }
}
