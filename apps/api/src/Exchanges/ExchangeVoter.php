<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;

/**
 * Decides whether the signed-in member can reach an exchange. They reach their own account's exchanges (ADR 0081).
 *
 * @extends AccountResourceVoter<ExchangeDetail>
 */
final class ExchangeVoter extends AccountResourceVoter
{
    public const string ACCESS = 'EXCHANGE_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof ExchangeDetail;
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownExchange';
    }
}
