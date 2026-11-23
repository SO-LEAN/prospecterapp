<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeChronology;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Turns an exchange into a plain document for its DynamoDB item, and back (ADR 0048). Its facts and its attachments
 * are stored as they are. Its recorded events are never stored. Older items read as follows:
 * - an item written before exchanges could be planned reads as done;
 * - before missed was a status, a call that did not go through was written done with the fact `missed`. It reads as
 *   missed;
 * - an item written before exchanges were logged in campaigns reads as in no campaign (ADR 0145);
 * - an item written before e-mails were imported reads as imported from nowhere (ADR 0154);
 * - an item written before bookers booked through a public link reads as booked by nobody (ADR 0164);
 * - an item written before exchanges kept their fingerprint reads with its e-mail's fingerprint when it was imported,
 *   and with none otherwise (ADR 0166);
 * - an item written before exchanges kept their origin reads as created by hand.
 */
final class ExchangeDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Exchange $exchange): array
    {
        $state = Hydrator::stateOf($exchange);
        $facts = Typed::object($state['facts'], ExchangeFacts::class);
        $importedFrom = Typed::objectOrNull($state['importedFrom'], ImportedEmail::class);

        return [
            'id' => Typed::object($state['id'], ExchangeId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'createdBy' => Typed::object($state['createdBy'], MemberId::class)->value,
            'contact' => Typed::objectOrNull($state['contact'], PersonId::class)?->value,
            'keptFor' => array_map(static fn (OrganizationId $organization): string => $organization->value, Typed::objects($state['keptFor'], OrganizationId::class)),
            'campaignId' => Typed::objectOrNull($state['campaign'], CampaignId::class)?->value,
            'importedFrom' => null === $importedFrom ? null : ['provider' => $importedFrom->provider, 'identity' => $importedFrom->identity, 'providerMessageId' => $importedFrom->providerMessageId],
            'bookedByLink' => Typed::bool($state['bookedByLink']),
            'origin' => Typed::object($state['origin'], Origin::class)->value,
            'fingerprint' => Typed::objectOrNull($state['fingerprint'], ExchangeFingerprint::class)?->value,
            'facts' => [
                'channel' => $facts->channel->value,
                'direction' => $facts->direction->value,
                'occurredAt' => Documents::moment($facts->occurredAt),
                'text' => $facts->text,
                'durationInMinutes' => $facts->duration?->minutes,
                'place' => $facts->place,
                'link' => $facts->link?->url,
                'linkRequested' => $facts->linkRequested,
            ],
            'status' => Typed::object($state['status'], ExchangeStatus::class)->value,
            'lastCorrection' => self::correctionDocument(Typed::objectOrNull($state['lastCorrection'], Correction::class)),
            'contactForgotten' => Typed::bool($state['contactForgotten']),
            'redacted' => self::correctionDocument(Typed::objectOrNull($state['madeConform'], Correction::class)),
            'cancellation' => self::cancellationDocument(Typed::objectOrNull($state['cancellation'], Cancellation::class)),
            'attachments' => array_map(self::attachmentDocument(...), Typed::objects($state['attachments'], Attachment::class)),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the exchange from its item's untyped document.
     */
    public static function exchange(mixed $document): Exchange
    {
        $document = Typed::document($document);
        $facts = Typed::document($document['facts']);
        $contact = Typed::stringOrNull($document['contact']);
        $campaign = Typed::stringOrNull($document['campaignId'] ?? null);

        return Hydrator::restore(Exchange::class, [
            'id' => new ExchangeId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'createdBy' => new MemberId(Typed::string($document['createdBy'])),
            'contact' => null === $contact ? null : new PersonId($contact),
            'keptFor' => self::keptForOf($document),
            'campaign' => null === $campaign ? null : new CampaignId($campaign),
            'importedFrom' => self::importedFromOf($document['importedFrom'] ?? null),
            'bookedByLink' => Typed::bool($document['bookedByLink'] ?? false),
            'origin' => Documents::originOf($document),
            'fingerprint' => self::fingerprintOf($document),
            'facts' => new ExchangeFacts(Channel::from(Typed::string($facts['channel'])), Direction::from(Typed::string($facts['direction'])), Documents::momentOf(Typed::string($facts['occurredAt'])), Typed::stringOrNull($facts['text']), self::duration($facts['durationInMinutes'] ?? null), Typed::stringOrNull($facts['place'] ?? null), self::link(Typed::stringOrNull($facts['link'] ?? null)), self::linkRequestedOf($document)),
            'status' => self::statusOf($document),
            'lastCorrection' => self::correctionOf($document['lastCorrection'] ?? null),
            'contactForgotten' => Typed::bool($document['contactForgotten'] ?? false),
            'madeConform' => self::correctionOf($document['redacted'] ?? null),
            'cancellation' => self::cancellation($document['cancellation'] ?? null),
            'attachments' => self::attachmentsOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<OrganizationId>
     */
    private static function keptForOf(array $document): array
    {
        return array_map(static fn (string $organization): OrganizationId => new OrganizationId($organization), Typed::strings($document['keptFor']));
    }

    private static function importedFromOf(mixed $email): ?ImportedEmail
    {
        if (null === $email) {
            return null;
        }
        $email = Typed::document($email);

        return new ImportedEmail(Typed::string($email['provider']), Typed::string($email['identity']), Typed::stringOrNull($email['providerMessageId'] ?? null));
    }

    /**
     * An exchange written before exchanges kept their fingerprint gets one only if it was imported. It then gets its
     * e-mail's fingerprint, unless its contact was forgotten. Any other exchange gets none (ADR 0166).
     *
     * @param array<string, mixed> $document
     */
    private static function fingerprintOf(array $document): ?ExchangeFingerprint
    {
        if (array_key_exists('fingerprint', $document)) {
            return null === $document['fingerprint'] ? null : Hydrator::restore(ExchangeFingerprint::class, ['value' => Typed::string($document['fingerprint'])]);
        }
        $email = self::importedFromOf($document['importedFrom'] ?? null);
        if (null === $email || Typed::bool($document['contactForgotten'] ?? false)) {
            return null;
        }
        $contact = Typed::stringOrNull($document['contact']);

        return ExchangeFingerprint::ofEmail($email->identity, null === $contact ? self::keptForOf($document)[0] : new PersonId($contact));
    }

    /**
     * An exchange written before exchanges had a status is done. Before missed was a status, a call that did not go
     * through was written done with its fact `missed`. Such a call is missed.
     *
     * @param array<string, mixed> $document
     */
    public static function statusOf(array $document): ExchangeStatus
    {
        $status = ExchangeStatus::tryFrom(Typed::stringOrNull($document['status'] ?? null) ?? '') ?? ExchangeStatus::Done;

        return ExchangeStatus::Done === $status ? ExchangeStatus::outcome(Typed::bool(self::factsOf($document)['missed'] ?? false)) : $status;
    }

    /**
     * A video call written before it could request its link from its holder's calendar (ADR 0137) requests none.
     *
     * @param array<string, mixed> $document
     */
    public static function linkRequestedOf(array $document): bool
    {
        return true === (self::factsOf($document)['linkRequested'] ?? false);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private static function factsOf(array $document): array
    {
        return Typed::document($document['facts'] ?? []);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private static function cancellationOf(array $document): array
    {
        return Typed::document($document['cancellation'] ?? []);
    }

    /**
     * Still to come: neither done, missed nor cancelled.
     *
     * @param array<string, mixed> $document
     */
    public static function isPlanned(array $document): bool
    {
        return ExchangeStatus::Planned === self::statusOf($document);
    }

    /**
     * Done or missed: neither still to come nor cancelled.
     *
     * @param array<string, mixed> $document
     */
    public static function tookPlace(array $document): bool
    {
        return in_array(self::statusOf($document), [ExchangeStatus::Done, ExchangeStatus::Missed], true);
    }

    /**
     * Who called the exchange off, as a value of `Canceller`. Null while it is not cancelled, or when nobody said who.
     *
     * @param array<string, mixed> $document
     */
    public static function cancellerOf(array $document): ?string
    {
        return Typed::stringOrNull(self::cancellationOf($document)['canceller'] ?? null);
    }

    /**
     * When the exchange stands in its timelines (`ExchangeChronology`).
     *
     * @param array<string, mixed> $document
     */
    public static function listedAt(array $document): \DateTimeImmutable
    {
        $cancelledAt = Typed::stringOrNull(self::cancellationOf($document)['at'] ?? null);

        return ExchangeChronology::listedAt(Documents::momentOf(Typed::string(self::factsOf($document)['occurredAt'])), null === $cancelledAt ? null : Documents::momentOf($cancelledAt));
    }

    /**
     * Restores the duration without its constructor, so a duration accepted once stays whatever the limits become.
     * Some durations were written as a string of digits.
     */
    private static function duration(mixed $minutes): ?Duration
    {
        return null === $minutes ? null : Hydrator::restore(Duration::class, ['minutes' => self::wholeNumberOf($minutes)]);
    }

    /**
     * The exchange's duration in minutes, or null when it was not given. Some durations were written as a string of
     * digits.
     *
     * @param array<string, mixed> $facts
     */
    public static function durationInMinutesOf(array $facts): ?int
    {
        $minutes = $facts['durationInMinutes'] ?? null;

        return null === $minutes ? null : self::wholeNumberOf($minutes);
    }

    private static function wholeNumberOf(mixed $number): int
    {
        return is_string($number) ? (int) $number : Typed::int($number);
    }

    /**
     * Restores the link without its constructor, like the duration, so a link accepted once stays.
     */
    private static function link(?string $url): ?VideoCallLink
    {
        return null === $url ? null : Hydrator::restore(VideoCallLink::class, ['url' => $url]);
    }

    /**
     * @return array{by: string, at: string}|null
     */
    private static function correctionDocument(?Correction $correction): ?array
    {
        return null === $correction ? null : ['by' => (string) $correction->by, 'at' => Documents::moment($correction->at)];
    }

    /**
     * @return array{by: string, at: string, canceller: ?string}|null
     */
    private static function cancellationDocument(?Cancellation $cancellation): ?array
    {
        return null === $cancellation ? null : ['by' => (string) $cancellation->by, 'at' => Documents::moment($cancellation->at), 'canceller' => $cancellation->canceller?->value];
    }

    private static function cancellation(mixed $document): ?Cancellation
    {
        if (null === $document) {
            return null;
        }
        $document = Typed::document($document);

        return new Cancellation(new MemberId(Typed::string($document['by'])), Documents::momentOf(Typed::string($document['at'])), Canceller::tryFrom(Typed::stringOrNull($document['canceller'] ?? null) ?? ''));
    }

    public static function correctionOf(mixed $document): ?Correction
    {
        if (null === $document) {
            return null;
        }
        $document = Typed::document($document);

        return new Correction(new MemberId(Typed::string($document['by'])), Documents::momentOf(Typed::string($document['at'])));
    }

    /**
     * @return array{id: string, name: string, size: int, mediaType: string, file: string}
     */
    private static function attachmentDocument(Attachment $attachment): array
    {
        return ['id' => (string) $attachment->id, 'name' => $attachment->name, 'size' => $attachment->size, 'mediaType' => $attachment->mediaType, 'file' => $attachment->file];
    }

    /**
     * Restores each file without its constructor, so a file accepted once stays whatever the limit becomes.
     *
     * @param array<string, mixed> $document
     *
     * @return list<Attachment>
     */
    public static function attachmentsOf(array $document): array
    {
        return array_map(self::attachment(...), Typed::documents($document['attachments'] ?? []));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function attachment(array $document): Attachment
    {
        return Hydrator::restore(Attachment::class, [
            'id' => new AttachmentId(Typed::string($document['id'])),
            'name' => Typed::string($document['name']),
            'size' => self::wholeNumberOf($document['size']),
            'mediaType' => Typed::string($document['mediaType']),
            'file' => Typed::string($document['file']),
        ]);
    }
}
