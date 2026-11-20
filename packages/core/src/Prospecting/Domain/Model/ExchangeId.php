<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

final readonly class ExchangeId extends Uuid
{
    private const string HASH = 'sha256';
    private const string NAME_BASED = '8';
    private const string RFC_VARIANT = '8';

    /**
     * The exchange of an e-mail imported with one correspondent of an account is always the same: no client gives the
     * identity of what a reaction imports, and an e-mail imported again finds the exchange it made (ADR 0154). A UUID
     * of version 8, built from the hash of what it is named by.
     */
    public static function ofImportedEmail(AccountId $account, string $email, PersonId|OrganizationId $correspondent): self
    {
        $hash = hash(self::HASH, sprintf('%s#%s#%s', $account->value, $email, $correspondent->value));

        return new self(sprintf('%s-%s-%s%s-%s%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), self::NAME_BASED, substr($hash, 13, 3), self::RFC_VARIANT, substr($hash, 17, 3), substr($hash, 20, 12)));
    }
}
