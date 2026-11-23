<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Reads an exchange's detail from the item `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository` writes,
 * without restoring the exchange (ADR 0088). It reads the exchange as its reader may read it. It adds what is to
 * redact, and what the exchange's state offers the reader at the moment of reading. The core's rules decide both.
 */
final readonly class DynamoExchangeDetailRetriever implements ExchangeDetailRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ExchangeId $id, Prospector $reader, \DateTimeImmutable $asOf, ?\DateTimeImmutable $movedFrom = null): ExchangeDetail
    {
        $item = $this->prospectingTable->item(ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE) ?? throw UnknownExchange::identifiedBy($id);

        return self::detailOf(Typed::document($item['exchange']), $reader, $asOf, $movedFrom);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function detailOf(array $document, Prospector $reader, \DateTimeImmutable $asOf, ?\DateTimeImmutable $movedFrom): ExchangeDetail
    {
        $facts = Typed::document($document['facts']);
        $occurredAt = Documents::momentOf(Typed::string($facts['occurredAt']));
        $channel = Typed::string($facts['channel']);
        $status = ExchangeDocument::statusOf($document);
        $text = Typed::stringOrNull($facts['text']);
        $contact = Typed::stringOrNull($document['contact']);
        $createdBy = Typed::string($document['createdBy']);
        $correction = ExchangeDocument::correctionOf($document['lastCorrection'] ?? null);
        $madeConform = ExchangeDocument::correctionOf($document['redacted'] ?? null);
        $contactForgotten = Typed::bool($document['contactForgotten'] ?? false);
        $attachments = ExchangeDocument::attachmentsOf($document);
        $read = ExchangeReading::readsTheAttachments($reader) ? $attachments : [];
        $attachmentsToMakeConform = ExchangeConformity::areAttachmentsToMakeConform($contactForgotten);
        $importedFrom = null === ($document['importedFrom'] ?? null) ? null : Typed::document($document['importedFrom']);

        return new ExchangeDetail(
            id: Typed::string($document['id']),
            accountId: Typed::string($document['accountId']),
            personId: $contact,
            contactPresence: ContactPresence::ofTheContact(null !== $contact, $contactForgotten)?->value,
            channel: $channel,
            direction: Typed::string($facts['direction']),
            occurredAt: $occurredAt,
            status: $status->value,
            canceller: ExchangeDocument::cancellerOf($document),
            durationInMinutes: ExchangeDocument::durationInMinutesOf($facts),
            text: $text,
            place: Typed::stringOrNull($facts['place'] ?? null),
            link: Typed::stringOrNull($facts['link'] ?? null),
            linkAwaited: Exchange::awaitsALink(ExchangeDocument::linkRequestedOf($document), $status),
            textFlagged: ExchangeReading::isTextFlagged(null !== $text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $text, $contactForgotten, null !== $madeConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, null !== $madeConform, [] !== $attachments),
            organizationIds: Typed::strings($document['keptFor']),
            createdBy: $createdBy,
            createdAt: Documents::momentOf(Typed::string($document['createdAt'])),
            correctedBy: $correction?->by->value,
            correctedAt: $correction?->at,
            madeConformBy: $madeConform?->by->value,
            madeConformAt: $madeConform?->at,
            attachments: array_map(static fn (Attachment $attachment): AttachmentDetail => new AttachmentDetail($attachment->id->value, $attachment->name, $attachment->size, $attachment->mediaType, $attachmentsToMakeConform), $read),
            hiddenAttachments: count($attachments) - count($read),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, ExchangePossibilities::given(new ExchangeSituation(count($attachments), ExchangeConformity::awaitsBeingMadeConform($contactForgotten, null !== $madeConform), $status, $occurredAt <= $asOf, $createdBy === $reader->id->value, Channel::from($channel), null !== $movedFrom && Exchange::isAMomentToCome($movedFrom, $asOf)))),
            campaignId: Typed::stringOrNull($document['campaignId'] ?? null),
            bookedByLink: Typed::bool($document['bookedByLink'] ?? false),
            importedFrom: null === $importedFrom ? null : Typed::string($importedFrom['provider']),
            providerMessageId: null === $importedFrom ? null : Typed::stringOrNull($importedFrom['providerMessageId'] ?? null),
            createdByAnAI: Origin::Ai === Documents::originOf($document),
        );
    }
}
