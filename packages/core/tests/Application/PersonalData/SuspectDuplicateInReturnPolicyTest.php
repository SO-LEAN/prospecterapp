<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Application\Duplicate\SuspectDuplicateInReturn\SuspectDuplicateInReturn;
use Prospecter\Core\PersonalData\Application\Policy\SuspectDuplicateInReturnPolicy;
use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspected;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class SuspectDuplicateInReturnPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string NEWCOMER = '01960000-0000-7000-8000-000000000003';
    private const string CONTACT = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testTheContactANewcomerResemblesSuspectsTheNewcomerInReturn(): void
    {
        $suspected = new DuplicateSuspected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new PersonId(self::NEWCOMER), new PersonId(self::CONTACT), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new SuspectDuplicateInReturn(
            memberId: self::MEMBER,
            contactId: self::CONTACT,
            duplicateId: self::NEWCOMER,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new SuspectDuplicateInReturnPolicy()($suspected));
    }
}
