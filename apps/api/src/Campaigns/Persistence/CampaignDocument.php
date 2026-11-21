<?php

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageMode;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\Recipients;
use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignProgress;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A campaign as its item keeps it (ADR 0048, 0144): its tags by their names without their mark, those of its target
 * derived from them and from its channel, never kept; frozen, its recipients by their identities and their statuses,
 * whether the call of one processed did not go through and the exchange that processed it (ADR 0145, 0152), why one
 * was dismissed, in the order they were added, none in a draft; the moments it was started, stopped and ended, none before.
 */
final class CampaignDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Campaign $campaign): array
    {
        $state = Hydrator::stateOf($campaign);
        $targeting = $state['targeting'];

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'name' => $state['name']->value,
            'channel' => $targeting->channel->value,
            'tags' => array_map(static fn (Tag $tag): string => $tag->name, $targeting->chosen->tags),
            'message' => $state['message']->text,
            'subject' => $state['message']->subject,
            'status' => $state['status']->value,
            'recipients' => array_map(self::recipientDocument(...), $state['recipients']->recipients),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
            'startedAt' => self::momentOrNone($state['startedAt']),
            'stoppedAt' => self::momentOrNone($state['stoppedAt']),
            'endedAt' => self::momentOrNone($state['endedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function campaign(array $document): Campaign
    {
        $channel = Channel::from($document['channel']);

        return Hydrator::restore(Campaign::class, [
            'id' => new CampaignId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'name' => CampaignName::of($document['name']),
            'targeting' => self::targetingOf($document),
            'message' => MessageTemplate::of($document['message'], $document['subject'], $channel),
            'status' => CampaignStatus::from($document['status']),
            'recipients' => Hydrator::restore(Recipients::class, ['recipients' => array_map(
                self::recipient(...),
                self::recipientsOf($document),
            )]),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
            'startedAt' => self::momentOfOrNone($document, 'startedAt'),
            'stoppedAt' => self::momentOfOrNone($document, 'stoppedAt'),
            'endedAt' => self::momentOfOrNone($document, 'endedAt'),
        ]);
    }

    /**
     * The read model, without restoring the campaign (ADR 0088).
     *
     * @param array<string, mixed> $document
     */
    public static function sheet(array $document): CampaignSheet
    {
        $targeting = self::targetingOf($document);
        $status = CampaignStatus::from($document['status']);
        $recipients = array_map(self::recipient(...), self::recipientsOf($document));
        $statuses = array_map(static fn (Recipient $recipient): RecipientStatus => $recipient->status, $recipients);

        return new CampaignSheet(
            id: $document['id'],
            accountId: $document['accountId'],
            name: $document['name'],
            channel: $document['channel'],
            tags: self::named($targeting->chosen),
            targetedTags: self::named($targeting->targeted()),
            message: $document['message'],
            subject: $document['subject'],
            messageMode: MessageMode::AsIs->value,
            status: $document['status'],
            followsTheTags: $status->followsTheTags(),
            running: $status->isRunning(),
            processed: CampaignProgress::processedAmong($statuses),
            dismissed: CampaignProgress::dismissedAmong($statuses),
            recipients: array_combine(
                array_map(static fn (Recipient $recipient): string => $recipient->contact->value, $recipients),
                array_map(static fn (Recipient $recipient): RecipientInSheet => new RecipientInSheet($recipient->status->value, $recipient->isPending(), $recipient->missed, $recipient->reason, $recipient->exchangeId, $recipient->erased), $recipients),
            ),
            authorId: $document['createdBy'],
            possibleIntents: array_map(static fn (CampaignIntent $intent): string => $intent->name, CampaignPossibilities::given($status, [] !== $recipients)),
            createdAt: Documents::momentOf($document['createdAt']),
            modifiedAt: Documents::momentOf($document['modifiedAt']),
            startedAt: self::momentOfOrNone($document, 'startedAt'),
            stoppedAt: self::momentOfOrNone($document, 'stoppedAt'),
            endedAt: self::momentOfOrNone($document, 'endedAt'),
        );
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<array{contactId: string, status: string, missed?: bool, reason?: ?string, exchangeId?: ?string, erased?: bool}> none for a campaign saved before it could be frozen
     */
    private static function recipientsOf(array $document): array
    {
        return $document['recipients'] ?? [];
    }

    /**
     * @return array{contactId: string, status: string, missed: bool, reason: ?string, exchangeId: ?string, erased: bool}
     */
    private static function recipientDocument(Recipient $recipient): array
    {
        return ['contactId' => $recipient->contact->value, 'status' => $recipient->status->value, 'missed' => $recipient->missed, 'reason' => $recipient->reason, 'exchangeId' => $recipient->exchangeId, 'erased' => $recipient->erased];
    }

    /**
     * Without whether it was missed nor why it was dismissed when saved before a campaign was led, without its exchange
     * when processed before the recipient kept it (ADR 0152); erased, by its status alone when saved before an erased
     * recipient kept what was done with them.
     *
     * @param array{contactId: string, status: string, missed?: bool, reason?: ?string, exchangeId?: ?string, erased?: bool} $document
     */
    private static function recipient(array $document): Recipient
    {
        return new Recipient(new PersonId($document['contactId']), RecipientStatus::from($document['status']), $document['missed'] ?? false, $document['reason'] ?? null, $document['exchangeId'] ?? null, $document['erased'] ?? RecipientStatus::Erased->value === $document['status']);
    }

    private static function momentOrNone(?\DateTimeImmutable $moment): ?string
    {
        return null === $moment ? null : Documents::moment($moment);
    }

    /**
     * None for a campaign saved before it could be started.
     *
     * @param array<string, mixed> $document
     */
    private static function momentOfOrNone(array $document, string $field): ?\DateTimeImmutable
    {
        $moment = $document[$field] ?? null;

        return null === $moment ? null : Documents::momentOf($moment);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function targetingOf(array $document): Targeting
    {
        return Targeting::by(Channel::from($document['channel']), array_map(static fn (string $name): string => Tag::MARK.$name, $document['tags']));
    }

    /**
     * @return list<string>
     */
    private static function named(TagSelection $selection): array
    {
        return array_map(strval(...), $selection->tags);
    }
}
