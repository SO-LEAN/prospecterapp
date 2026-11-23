<?php

declare(strict_types=1);

namespace Tests\Core\Application\Campaigns;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Application\ForgetTagsOfUnprospectedOrganization\ForgetTagsOfUnprospectedOrganization;
use Prospecter\Core\Campaigns\Application\Policy\ForgetTagsOfUnprospectedOrganizationPolicy;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;

class ForgetTagsOfUnprospectedOrganizationPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string ORGANIZATION = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAnOrganizationNoLongerProspectedForgetsItsTags(): void
    {
        $terminal = new OrganizationFactsChanged(self::ACCOUNT, self::ORGANIZATION, 0, false, false, [], null, null, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ForgetTagsOfUnprospectedOrganization(self::ACCOUNT, self::ORGANIZATION, new \DateTimeImmutable(self::AT)), new ForgetTagsOfUnprospectedOrganizationPolicy()($terminal));
    }

    public function testAnOrganizationStillProspectedKeepsItsTags(): void
    {
        $present = new OrganizationFactsChanged(self::ACCOUNT, self::ORGANIZATION, 4, true, true, [], '75056', 'FR', new \DateTimeImmutable(self::AT));

        $this->assertNull(new ForgetTagsOfUnprospectedOrganizationPolicy()($present));
    }
}
