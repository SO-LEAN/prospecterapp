<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;

/**
 * Reads the item `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository` writes as the detail of the
 * exchange, without restoring it (ADR 0088): as its reader may read it, with what is to make conform and what
 * its state offers, asked to the policies of the core.
 */
final readonly class DynamoExchangeDetailRetriever implements ExchangeDetailRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ExchangeId $id, Prospector $reader): ExchangeDetail
    {
        $item = $this->prospectingTable->item(ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE) ?? throw UnknownExchange::identifiedBy($id);

        return self::detailOf($item['exchange'], $reader);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function detailOf(array $document, Prospector $reader): ExchangeDetail
    {
        $facts = $document['facts'];
        $hasText = null !== $facts['text'];
        $correction = $document['lastCorrection'] ?? null;
        $madeConform = $document['madeConform'] ?? null;
        $contactForgotten = $document['contactForgotten'] ?? false;
        $attachments = $document['attachments'] ?? [];
        $read = ExchangeReading::readsTheAttachments($reader) ? $attachments : [];
        $attachmentsToMakeConform = ExchangeConformity::areAttachmentsToMakeConform($contactForgotten);

        return new ExchangeDetail(
            id: $document['id'],
            accountId: $document['accountId'],
            personId: $document['contact'],
            contactPresence: ContactPresence::ofTheContact(null !== $document['contact'], $contactForgotten)?->value,
            channel: $facts['channel'],
            direction: $facts['direction'],
            missed: $facts['missed'],
            occurredAt: Documents::momentOf($facts['occurredAt']),
            text: $facts['text'],
            textFlagged: ExchangeReading::isTextFlagged($hasText, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform($hasText, $contactForgotten, null !== $madeConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, null !== $madeConform, [] !== $attachments),
            organizationIds: $document['keptFor'],
            createdBy: $document['createdBy'],
            createdAt: Documents::momentOf($document['createdAt']),
            correctedBy: $correction['by'] ?? null,
            correctedAt: null === $correction ? null : Documents::momentOf($correction['at']),
            madeConformBy: $madeConform['by'] ?? null,
            madeConformAt: null === $madeConform ? null : Documents::momentOf($madeConform['at']),
            attachments: array_map(static fn (array $attachment): AttachmentDetail => new AttachmentDetail($attachment['id'], $attachment['name'], (int) $attachment['size'], $attachment['mediaType'], $attachmentsToMakeConform), $read),
            hiddenAttachments: count($attachments) - count($read),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, ExchangePossibilities::given(count($attachments), ExchangeConformity::awaitsBeingMadeConform($contactForgotten, null !== $madeConform))),
        );
    }
}
