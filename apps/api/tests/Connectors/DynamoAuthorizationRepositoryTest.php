<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use App\Connectors\Persistence\ConnectorsKeys;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Model\AuthorizationRequest;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The authorizations waiting for their answer in the table of Connectors: the rule of the core refuses one ten minutes
 * after it was asked, the table forgets it an hour after it was kept, counted on the clock of what the tables forget
 * (far after the real one in the tests: that of the controllers is in the past, the table would forget the
 * authorization while its answer is on its way).
 */
class DynamoAuthorizationRepositoryTest extends ApiTestCase
{
    private const string STATE = '0123456789abcdef0123456789abcdef';

    public function testTheTableForgetsAnAuthorizationAnHourAfterItWasKept(): void
    {
        $asked = PendingAuthorization::askedBy(new MemberId(self::newIdentity()), new AuthorizationRequest(Provider::Google, self::STATE, 'a-verifier'), new \DateTimeImmutable('2026-10-15 09:00:00 UTC'));

        self::getContainer()->get(AuthorizationRepository::class)->keep($asked);

        $item = $this->connectorsTable()->item(ConnectorsKeys::authorization(self::STATE), ConnectorsKeys::AUTHORIZATION);
        $this->assertSame(new \DateTimeImmutable('2100-01-01 01:00:00 UTC')->getTimestamp(), $item['expiresAt']);
        $this->assertEquals($asked, self::getContainer()->get(AuthorizationRepository::class)->find(self::STATE));
    }

    public function testAnAuthorizationKeepsTheScopesAsked(): void
    {
        $asked = PendingAuthorization::askedBy(new MemberId(self::newIdentity()), new AuthorizationRequest(Provider::Google, self::STATE, 'a-verifier', [Scope::Calendar, Scope::Mailbox]), new \DateTimeImmutable('2026-10-15 09:00:00 UTC'));

        self::getContainer()->get(AuthorizationRepository::class)->keep($asked);

        $this->assertEquals($asked, self::getContainer()->get(AuthorizationRepository::class)->find(self::STATE));
    }

    private function connectorsTable(): Table
    {
        $table = self::getContainer()->get('app.table.connectors');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
