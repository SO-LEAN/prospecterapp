<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\EraseExchangesOfContactLeftUnattached\EraseExchangesOfContactLeftUnattached;
use Prospecter\Core\Prospecting\Application\Policy\EraseExchangesOfContactLeftUnattachedPolicy;
use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class EraseExchangesOfContactLeftUnattachedPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testTheExchangesOfAContactLeftUnattachedAreErased(): void
    {
        $event = new ContactLeftUnattached(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ContactId(self::CONTACT), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new EraseExchangesOfContactLeftUnattached(
            accountId: self::ACCOUNT,
            holderId: self::MEMBER,
            contactId: self::CONTACT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new EraseExchangesOfContactLeftUnattachedPolicy()($event));
    }
}
