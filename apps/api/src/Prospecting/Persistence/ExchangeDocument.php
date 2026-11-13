<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeChronology;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An exchange as a plain document of its DynamoDB item, and back (ADR 0048): its facts and its attachments as
 * they are, its recorded events never; an item written before an exchange was planned reads as done, and one
 * written done with the fact `missed` of a call that did not go through, before missed was a status, as missed.
 */
final class ExchangeDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Exchange $exchange): array
    {
        $state = Hydrator::stateOf($exchange);
        $facts = $state['facts'];

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'contact' => $state['contact']?->value,
            'keptFor' => array_map(static fn (OrganizationId $organization): string => (string) $organization, $state['keptFor']),
            'facts' => [
                'channel' => $facts->channel->value,
                'direction' => $facts->direction->value,
                'occurredAt' => Documents::moment($facts->occurredAt),
                'text' => $facts->text,
                'durationInMinutes' => $facts->duration?->minutes,
                'place' => $facts->place,
                'link' => $facts->link?->url,
            ],
            'status' => $state['status']->value,
            'lastCorrection' => self::correctionDocument($state['lastCorrection']),
            'contactForgotten' => $state['contactForgotten'],
            'madeConform' => self::correctionDocument($state['madeConform']),
            'cancellation' => self::cancellationDocument($state['cancellation']),
            'attachments' => array_map(self::attachmentDocument(...), $state['attachments']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function exchange(array $document): Exchange
    {
        $facts = $document['facts'];

        return Hydrator::restore(Exchange::class, [
            'id' => new ExchangeId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'contact' => null === $document['contact'] ? null : new PersonId($document['contact']),
            'keptFor' => array_map(static fn (string $organization): OrganizationId => new OrganizationId($organization), $document['keptFor']),
            'facts' => new ExchangeFacts(Channel::from($facts['channel']), Direction::from($facts['direction']), Documents::momentOf($facts['occurredAt']), $facts['text'], self::duration($facts['durationInMinutes'] ?? null), $facts['place'] ?? null, self::link($facts['link'] ?? null)),
            'status' => self::statusOf($document),
            'lastCorrection' => self::correction($document['lastCorrection'] ?? null),
            'contactForgotten' => $document['contactForgotten'] ?? false,
            'madeConform' => self::correction($document['madeConform'] ?? null),
            'cancellation' => self::cancellation($document['cancellation'] ?? null),
            'attachments' => array_map(self::attachment(...), $document['attachments'] ?? []),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * Written before an exchange had a status, it is done; before missed was a status, a call that did not go through
     * was done with its fact `missed`: it is missed.
     *
     * @param array<string, mixed> $document
     */
    public static function statusOf(array $document): ExchangeStatus
    {
        $status = ExchangeStatus::tryFrom($document['status'] ?? '') ?? ExchangeStatus::Done;

        return ExchangeStatus::Done === $status ? ExchangeStatus::outcome($document['facts']['missed'] ?? false) : $status;
    }

    /**
     * Who called the exchange off, by the values of `Canceller`; none while it is not cancelled, or when not said.
     *
     * @param array<string, mixed> $document
     */
    public static function cancellerOf(array $document): ?string
    {
        return $document['cancellation']['canceller'] ?? null;
    }

    /**
     * When the exchange stands in its timelines (`ExchangeChronology`).
     *
     * @param array<string, mixed> $document
     */
    public static function listedAt(array $document): \DateTimeImmutable
    {
        $cancelledAt = $document['cancellation']['at'] ?? null;

        return ExchangeChronology::listedAt(Documents::momentOf($document['facts']['occurredAt']), null === $cancelledAt ? null : Documents::momentOf($cancelledAt));
    }

    /**
     * Restored without its constructor: a duration accepted once stays, whatever the limits become.
     */
    private static function duration(int|string|null $minutes): ?Duration
    {
        return null === $minutes ? null : Hydrator::restore(Duration::class, ['minutes' => (int) $minutes]);
    }

    /**
     * Restored without its constructor, as the duration: a link accepted once stays.
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

    /**
     * @param array{by: string, at: string, canceller: ?string}|null $document
     */
    private static function cancellation(?array $document): ?Cancellation
    {
        return null === $document ? null : new Cancellation(new MemberId($document['by']), Documents::momentOf($document['at']), Canceller::tryFrom($document['canceller'] ?? ''));
    }

    /**
     * @param array{by: string, at: string}|null $document
     */
    private static function correction(?array $document): ?Correction
    {
        return null === $document ? null : new Correction(new MemberId($document['by']), Documents::momentOf($document['at']));
    }

    /**
     * @return array{id: string, name: string, size: int, mediaType: string, file: string}
     */
    private static function attachmentDocument(Attachment $attachment): array
    {
        return ['id' => (string) $attachment->id, 'name' => $attachment->name, 'size' => $attachment->size, 'mediaType' => $attachment->mediaType, 'file' => $attachment->file];
    }

    /**
     * Restored without its constructor: a file accepted once stays, whatever the limit becomes.
     *
     * @param array{id: string, name: string, size: int|string, mediaType: string, file: string} $document
     */
    private static function attachment(array $document): Attachment
    {
        return Hydrator::restore(Attachment::class, [
            'id' => new AttachmentId($document['id']),
            'name' => $document['name'],
            'size' => (int) $document['size'],
            'mediaType' => $document['mediaType'],
            'file' => $document['file'],
        ]);
    }
}
