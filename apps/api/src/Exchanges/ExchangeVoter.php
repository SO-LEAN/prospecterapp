<?php

namespace App\Exchanges;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;

/**
 * Whether the signed-in member reaches an exchange: one of their account (ADR 0081).
 *
 * @extends AccountResourceVoter<ExchangeDetailResponse>
 */
final class ExchangeVoter extends AccountResourceVoter
{
    public const string ACCESS = 'EXCHANGE_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof ExchangeDetailResponse;
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
