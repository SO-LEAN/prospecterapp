<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\CampaignNameTooLong;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutName;

/** What the team calls a campaign: 1 to 100 characters, trimmed of spaces at both ends. */
final readonly class CampaignName
{
    public const int MAX = 100;

    private function __construct(public string $value)
    {
    }

    /**
     * @throws CampaignWithoutName
     * @throws CampaignNameTooLong
     */
    public static function of(string $typed): self
    {
        $name = trim($typed);

        return match (true) {
            '' === $name => throw CampaignWithoutName::because('A campaign has a name', 'name'),
            mb_strlen($name) > self::MAX => throw CampaignNameTooLong::longerThan(self::MAX),
            default => new self($name),
        };
    }
}
