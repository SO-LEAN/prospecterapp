<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\EraseToDoLeftWithoutOrganization\EraseToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Application\Policy\EraseToDoLeftWithoutOrganizationPolicy;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class EraseToDoLeftWithoutOrganizationPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string SUBJECT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAToDoLeftWithoutOrganizationIsErased(): void
    {
        $event = new ToDoLeftWithoutOrganization(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ToDoId(self::SUBJECT), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new EraseToDoLeftWithoutOrganization(
            memberId: self::MEMBER,
            toDoId: self::SUBJECT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new EraseToDoLeftWithoutOrganizationPolicy()($event));
    }
}
