<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\Typed;
use EmailReplyParser\EmailReplyParser;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\SharedKernel\Domain\Email;
use Soundasleep\Html2Text;

/**
 * A message as Gmail returns it (`messages.get`, `format=full`), read as a mailbox e-mail (ADR 0154). Its text is its
 * first plain-text part that is not an attachment. The text leaves out the quoted history and a signature after `-- `.
 *
 * @see https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages#Message
 */
final readonly class GmailMessage
{
    private const int MILLISECONDS = 1000;
    private const string PLAIN_TEXT = 'text/plain';
    private const string HTML = 'text/html';
    private const string UTF8 = 'UTF-8';
    private const string ATTACHMENT = 'attachment';

    /**
     * Returns null when the message has no sender address, because there is nobody to recognize.
     *
     * @param array<string, mixed> $message
     */
    public static function read(array $message): ?MailboxMail
    {
        $payload = Typed::document($message['payload'] ?? []);
        $headers = self::headersOf($payload);
        $sender = self::addressIn($headers['from'] ?? '');
        if (!self::isAnAddress($sender)) {
            return null;
        }

        return new MailboxMail(
            Typed::string($message['id']),
            $headers['message-id'] ?? null,
            new Email($sender),
            self::recipientsIn($headers['to'] ?? ''),
            Typed::strings($message['labelIds'] ?? []),
            new \DateTimeImmutable('@'.intdiv((int) Typed::string($message['internalDate']), self::MILLISECONDS)),
            $headers['subject'] ?? '',
            self::textOf($payload),
            self::attachmentsIn($payload),
        );
    }

    /**
     * The parts that have a file name, including nested parts, in their order. Images embedded in the body are left out
     * (ADR 0182).
     *
     * @param array<string, mixed> $part
     *
     * @return list<MailboxAttachment>
     */
    private static function attachmentsIn(array $part): array
    {
        $body = self::bodyIn($part);
        $attachments = self::isAttached($part) ? [new MailboxAttachment(Typed::string($body['attachmentId'] ?? ''), Typed::string($part['filename']), Typed::int($body['size'] ?? 0))] : [];

        return array_merge($attachments, ...array_map(self::attachmentsIn(...), self::partsIn($part)));
    }

    /**
     * A part with a Content-ID that is not given as an attachment shows in the body, like a signature's logo. A file
     * shown inline without a Content-ID (Apple Mail) is still an attachment.
     *
     * @param array<string, mixed> $part
     */
    private static function isAttached(array $part): bool
    {
        $headers = self::headersOf($part);
        $embedded = isset($headers['content-id']) && !str_starts_with(mb_strtolower(trim($headers['content-disposition'] ?? '')), self::ATTACHMENT);

        return '' !== ($part['filename'] ?? '') && !$embedded;
    }

    /**
     * @param array<string, mixed> $part
     *
     * @return array<string, string> each header's value, by its lower-case name
     */
    private static function headersOf(array $part): array
    {
        $headers = [];
        foreach (Typed::documents($part['headers'] ?? []) as $header) {
            $headers[mb_strtolower(Typed::string($header['name']))] = Typed::string($header['value']);
        }

        return $headers;
    }

    /**
     * Each address in the list. A value that is not an address is left out.
     *
     * @return list<Email>
     */
    private static function recipientsIn(string $to): array
    {
        $addresses = array_filter(array_map(self::addressIn(...), explode(',', $to)), self::isAnAddress(...));

        return array_values(array_map(static fn (string $address): Email => new Email($address), $addresses));
    }

    private static function isAnAddress(string $address): bool
    {
        return false !== filter_var($address, \FILTER_VALIDATE_EMAIL);
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
     * Its plain-text part, or else its HTML part converted to text.
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
        return (string) mb_convert_encoding(self::decoded(Typed::string(self::bodyIn($part)['data'] ?? '')), self::UTF8, self::charsetOf($part));
    }

    /**
     * @param array<string, mixed> $part
     *
     * @return array<string, mixed>
     */
    private static function bodyIn(array $part): array
    {
        return Typed::document($part['body'] ?? []);
    }

    /**
     * @param array<string, mixed> $part
     *
     * @return list<array<string, mixed>>
     */
    private static function partsIn(array $part): array
    {
        return Typed::documents($part['parts'] ?? []);
    }

    /**
     * The charset that its `Content-Type` header names. It is UTF-8 when the header names none, or one that PHP
     * does not know.
     *
     * @param array<string, mixed> $part
     */
    private static function charsetOf(array $part): string
    {
        $named = preg_match('/charset="?([\w.:-]+)"?/i', self::headersOf($part)['content-type'] ?? '', $charset) ? $charset[1] : self::UTF8;

        return in_array(mb_strtoupper($named), array_map(mb_strtoupper(...), mb_list_encodings()), true) ? $named : self::UTF8;
    }

    /**
     * The first part of this type that is not an attachment. Nested parts are read in their order.
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

        foreach (self::partsIn($part) as $child) {
            $found = self::firstPart($child, $mimeType);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Gmail returns a part's body in base64url.
     */
    public static function decoded(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
