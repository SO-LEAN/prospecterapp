<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Duplicate\SuspectDuplicateInReturn\SuspectDuplicateInReturn;
use Prospecter\Core\Prospecting\Application\Policy\SuspectDuplicateInReturnPolicy;
use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspected;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class SuspectDuplicateInReturnPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string ORGANIZATION = '01960000-0000-7000-8000-000000000003';
    private const string OTHER = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testTheOrganizationANewcomerResemblesSuspectsTheNewcomerInReturn(): void
    {
        $event = new DuplicateSuspected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new OrganizationId(self::ORGANIZATION), new OrganizationId(self::OTHER), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new SuspectDuplicateInReturn(
            memberId: self::MEMBER,
            organizationId: self::OTHER,
            duplicateId: self::ORGANIZATION,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new SuspectDuplicateInReturnPolicy()($event));
    }
}
