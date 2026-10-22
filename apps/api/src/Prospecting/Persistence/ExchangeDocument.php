<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Attachment;
use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Correction;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An exchange as a plain document of its DynamoDB item, and back (ADR 0048): its facts and its attachments as
 * they are, its recorded events never.
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
                'missed' => $facts->missed,
            ],
            'lastCorrection' => self::correctionDocument($state['lastCorrection']),
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
            'facts' => new ExchangeFacts(Channel::from($facts['channel']), Direction::from($facts['direction']), Documents::momentOf($facts['occurredAt']), $facts['text'], $facts['missed']),
            'lastCorrection' => self::correction($document['lastCorrection'] ?? null),
            'attachments' => array_map(self::attachment(...), $document['attachments'] ?? []),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * @return array{by: string, at: string}|null
     */
    private static function correctionDocument(?Correction $correction): ?array
    {
        return null === $correction ? null : ['by' => (string) $correction->by, 'at' => Documents::moment($correction->at)];
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
