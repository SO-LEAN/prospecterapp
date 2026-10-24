<?php

namespace App\Exchanges;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;

/**
 * What the signed-in member may do with an exchange of their account (ADR 0081): every prospector reads it,
 * retouches its text and declares it conform (ADR 0078); the rest needs the right to manage personal data.
 *
 * @extends AccountResourceVoter<ExchangeDetailResponse>
 */
final class ExchangeVoter extends AccountResourceVoter
{
    public const string CONSULT = 'ConsultExchange';
    public const string DOWNLOAD_ATTACHMENT = 'DownloadAttachment';
    public const string CORRECT_EXCHANGE = 'CorrectExchange';
    public const string CORRECT_TEXT = 'CorrectText';
    public const string MAKE_CONFORM = 'MakeConform';
    public const string ERASE_EXCHANGE = 'EraseExchange';
    public const string ATTACH_FILE = 'AttachFile';
    public const string REMOVE_ATTACHMENT = 'RemoveAttachment';
    private const array OPEN_TO_EVERY_PROSPECTOR = [self::CONSULT, self::CORRECT_TEXT, self::MAKE_CONFORM];
    private const array ATTRIBUTES = [
        self::CONSULT, self::DOWNLOAD_ATTACHMENT, self::CORRECT_EXCHANGE, self::CORRECT_TEXT, self::MAKE_CONFORM, self::ERASE_EXCHANGE,
        self::ATTACH_FILE, self::REMOVE_ATTACHMENT,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof ExchangeDetailResponse && in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownExchange';
    }

    protected function needsThePersonalDataRight(string $attribute, object $subject): bool
    {
        return !in_array($attribute, self::OPEN_TO_EVERY_PROSPECTOR, true);
    }
}
