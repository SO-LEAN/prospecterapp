<?php

namespace App\Connectors\Google;

use EmailReplyParser\EmailReplyParser;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\SharedKernel\Domain\Email;
use Soundasleep\Html2Text;

/**
 * A message as Gmail answers it (`messages.get`, `format=full`), read as an e-mail of the mailbox (ADR 0154): its
 * text is its first part in plain text, not attached, the quoted history and a signature after `-- ` left out.
 *
 * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages#Message
 */
final readonly class GmailMessage
{
    private const int MILLISECONDS = 1000;
    private const string PLAIN_TEXT = 'text/plain';
    private const string HTML = 'text/html';
    private const string UTF8 = 'UTF-8';

    /**
     * None without the address of its sender: nobody to recognize.
     *
     * @param array<string, mixed> $message
     */
    public static function read(array $message): ?MailboxMail
    {
        $headers = self::headersOf($message['payload'] ?? []);
        $sender = self::addressIn($headers['from'] ?? '');
        if (false === filter_var($sender, \FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return new MailboxMail(
            (string) $message['id'],
            $headers['message-id'] ?? null,
            new Email($sender),
            [],
            array_values($message['labelIds'] ?? []),
            new \DateTimeImmutable('@'.intdiv((int) $message['internalDate'], self::MILLISECONDS)),
            $headers['subject'] ?? '',
            self::textOf($message['payload'] ?? []),
        );
    }

    /**
     * @param array<string, mixed> $part
     *
     * @return array<string, string> the value of each header, by its name in lower case
     */
    private static function headersOf(array $part): array
    {
        $headers = [];
        foreach ($part['headers'] ?? [] as $header) {
            $headers[mb_strtolower($header['name'])] = $header['value'];
        }

        return $headers;
    }

    private static function addressIn(string $from): string
    {
        return preg_match('/<([^>]+)>/', $from, $address) ? $address[1] : trim($from);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function textOf(array $payload): string
    {
        return trim(EmailReplyParser::parseReply(self::plainTextOf($payload)));
    }

    /**
     * Its part in plain text, or else its part in HTML converted.
     *
     * @param array<string, mixed> $payload
     */
    private static function plainTextOf(array $payload): string
    {
        $plain = self::firstPart($payload, self::PLAIN_TEXT);
        if (null !== $plain) {
            return self::bodyOf($plain);
        }

        $html = self::firstPart($payload, self::HTML);

        return null === $html ? '' : Html2Text::convert(self::bodyOf($html), ['ignore_errors' => true]);
    }

    /**
     * @param array<string, mixed> $part
     */
    private static function bodyOf(array $part): string
    {
        return (string) mb_convert_encoding(self::decoded((string) ($part['body']['data'] ?? '')), self::UTF8, self::charsetOf($part));
    }

    /**
     * The charset its header `Content-Type` names, UTF-8 when it names none or one PHP does not know.
     *
     * @param array<string, mixed> $part
     */
    private static function charsetOf(array $part): string
    {
        $named = preg_match('/charset="?([\w.:-]+)"?/i', self::headersOf($part)['content-type'] ?? '', $charset) ? $charset[1] : self::UTF8;

        return in_array(mb_strtoupper($named), array_map(mb_strtoupper(...), mb_list_encodings()), true) ? $named : self::UTF8;
    }

    /**
     * The first part of this type, not attached, the parts of the parts read in their order.
     *
     * @param array<string, mixed> $part
     *
     * @return array<string, mixed>|null
     */
    private static function firstPart(array $part, string $mimeType): ?array
    {
        if ($mimeType === ($part['mimeType'] ?? null) && '' === ($part['filename'] ?? '')) {
            return $part;
        }

        foreach ($part['parts'] ?? [] as $child) {
            $found = self::firstPart($child, $mimeType);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Gmail answers the body of a part in base64url.
     */
    private static function decoded(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
