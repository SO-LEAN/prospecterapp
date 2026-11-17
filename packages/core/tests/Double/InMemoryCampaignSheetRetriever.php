<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\Recipients;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignProgress;
use Prospecter\Core\SharedKernel\Domain\AccountId;

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

    public function ofAccount(AccountId $accountId): array
    {
        $sheets = array_map(self::sheetOf(...), $this->database->campaignsOf($accountId->value));
        usort($sheets, static fn (CampaignSheet $one, CampaignSheet $other): int => strcmp($other->id, $one->id));

        return $sheets;
    }

    private static function sheetOf(Campaign $campaign): CampaignSheet
    {
        $state = StateOf::the($campaign);
        $targeting = $state['targeting'];

        return new CampaignSheet(
            id: $campaign->id()->value,
            accountId: $campaign->accountId()->value,
            name: $state['name']->value,
            channel: $targeting->channel->value,
            tags: self::named($targeting->chosen),
            targetedTags: self::named($targeting->targeted()),
            message: $state['message']->text,
            subject: $state['message']->subject,
            status: $state['status']->value,
            followsTheTags: $state['status']->followsTheTags(),
            contacted: CampaignProgress::contactedIn($state['status']),
            recipients: self::statusesOf($state['recipients']),
            authorId: $state['createdBy']->value,
            possibleIntents: array_map(static fn (CampaignIntent $intent): string => $intent->name, CampaignPossibilities::given($state['status'])),
            createdAt: $state['createdAt'],
            modifiedAt: $state['modifiedAt'],
        );
    }

    /**
     * @return array<string, string>
     */
    private static function statusesOf(Recipients $recipients): array
    {
        $statuses = [];

        foreach ($recipients->recipients as $recipient) {
            $statuses[$recipient->contact->value] = $recipient->status->value;
        }

        return $statuses;
    }

    /**
     * @return list<string>
     */
    private static function named(TagSelection $selection): array
    {
        return array_map(strval(...), $selection->tags);
    }
}
