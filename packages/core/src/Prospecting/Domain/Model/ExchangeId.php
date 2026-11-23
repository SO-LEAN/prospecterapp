<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

final readonly class ExchangeId extends Uuid
{
    /**
     * An e-mail imported with one of an account's correspondents always gives the same exchange. No client gives the
     * identity of what a reaction imports, and an e-mail imported again finds the exchange it made (ADR 0154).
     */
    public static function ofImportedEmail(AccountId $account, string $email, ContactId|OrganizationId $correspondent): self
    {
        return self::namedBy(sprintf('%s#%s#%s', $account->value, $email, $correspondent->value));
    }
}
