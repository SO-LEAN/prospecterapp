<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Policy\StopKeepingToDosPolicy;
use Prospecter\Core\Prospecting\Application\StopKeepingToDos\StopKeepingToDos;
use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class StopKeepingToDosPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string SUBJECT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAnOrganizationNoLongerProspectedStopsKeepingItsToDos(): void
    {
        $event = new ProspectingStopped(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new OrganizationId(self::SUBJECT), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new StopKeepingToDos(
            memberId: self::MEMBER,
            organizationId: self::SUBJECT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new StopKeepingToDosPolicy()($event));
    }
}
