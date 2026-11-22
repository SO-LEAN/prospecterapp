<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\Recipients;
use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignProgress;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The DynamoDB retriever in the scenarios: the campaigns the repository saved, read without their getters, the newest
 * first.
 */
final readonly class InMemoryCampaignSheetRetriever implements CampaignSheetRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(AccountId $accountId, CampaignId $id): CampaignSheet
    {
        return self::sheetOf($this->database->campaign($accountId->value, $id->value) ?? throw UnknownCampaign::identifiedBy($id));
    }

    public function find(AccountId $accountId, CampaignId $id): ?CampaignSheet
    {
        $campaign = $this->database->campaign($accountId->value, $id->value);

        return null === $campaign ? null : self::sheetOf($campaign);
    }

    /**
     * @param list<CampaignId> $ids
     */
    public function namesOf(AccountId $accountId, array $ids): array
    {
        $names = [];

        foreach ($ids as $id) {
            $campaign = $this->database->campaign($accountId->value, $id->value);

            if (null !== $campaign) {
                $names[$id->value] = StateOf::the($campaign)->object('name', CampaignName::class)->value;
            }
        }

        return $names;
    }

    public function ofAccount(AccountId $accountId): array
    {
        $sheets = array_map(self::sheetOf(...), $this->database->campaignsOf($accountId->value));
        usort($sheets, static fn (CampaignSheet $one, CampaignSheet $other): int => strcmp($other->id, $one->id));

        return $sheets;
    }

    private static function sheetOf(Campaign $campaign): CampaignSheet
    {
        $state = StateOf::the($campaign);
        $targeting = $state->object('targeting', Targeting::class);

        return new CampaignSheet(
            id: $campaign->id()->value,
            accountId: $campaign->accountId()->value,
            name: $state->object('name', CampaignName::class)->value,
            channel: $targeting->channel->value,
            tags: self::named($targeting->chosen),
            targetedTags: self::named($targeting->targeted()),
            message: $state->object('message', MessageTemplate::class)->text,
            subject: $state->object('message', MessageTemplate::class)->subject,
            messageMode: $state->object('message', MessageTemplate::class)->mode->value,
            status: $state->object('status', CampaignStatus::class)->value,
            followsTheTags: $state->object('status', CampaignStatus::class)->followsTheTags(),
            running: $state->object('status', CampaignStatus::class)->isRunning(),
            processed: CampaignProgress::processedAmong(self::statusesOf($state->object('recipients', Recipients::class))),
            dismissed: CampaignProgress::dismissedAmong(self::statusesOf($state->object('recipients', Recipients::class))),
            recipients: self::recipientsOf($state->object('recipients', Recipients::class)),
            authorId: $state->object('createdBy', MemberId::class)->value,
            possibleIntents: array_map(static fn (CampaignIntent $intent): string => $intent->name, CampaignPossibilities::given($state->object('status', CampaignStatus::class), $state->object('recipients', Recipients::class)->keepsSomeone())),
            createdAt: $state->object('createdAt', \DateTimeImmutable::class),
            modifiedAt: $state->object('modifiedAt', \DateTimeImmutable::class),
            startedAt: $state->objectOrNull('startedAt', \DateTimeImmutable::class),
            stoppedAt: $state->objectOrNull('stoppedAt', \DateTimeImmutable::class),
            endedAt: $state->objectOrNull('endedAt', \DateTimeImmutable::class),
        );
    }

    /**
     * @return array<string, RecipientInSheet>
     */
    private static function recipientsOf(Recipients $recipients): array
    {
        $read = [];

        foreach ($recipients->recipients as $recipient) {
            $read[$recipient->contact->value] = new RecipientInSheet($recipient->status->value, $recipient->isPending(), $recipient->missed, $recipient->reason, $recipient->exchangeId, $recipient->erased);
        }

        return $read;
    }

    /**
     * @return list<RecipientStatus>
     */
    private static function statusesOf(Recipients $recipients): array
    {
        return array_map(static fn (Recipient $recipient): RecipientStatus => $recipient->status, $recipients->recipients);
    }

    /**
     * @return list<string>
     */
    private static function named(TagSelection $selection): array
    {
        return array_map(strval(...), $selection->tags);
    }
}
