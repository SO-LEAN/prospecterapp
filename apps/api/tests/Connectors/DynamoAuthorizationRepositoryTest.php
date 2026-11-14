<?php

namespace Tests\App\Connectors;

use App\Connectors\Persistence\ConnectorsKeys;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The authorizations waiting for their answer in the table of Connectors: the rule of the core refuses one ten minutes
 * after it was asked, the table forgets it an hour after it was kept, by the real clock (that of the tests is in the
 * past: the table would forget the authorization while its answer is on its way).
 */
class DynamoAuthorizationRepositoryTest extends ApiTestCase
{
    private const string STATE = '0123456789abcdef0123456789abcdef';

    public function testTheTableForgetsAnAuthorizationAnHourAfterItWasKept(): void
    {
        $asked = PendingAuthorization::askedBy(new MemberId(self::newIdentity()), Provider::Google, self::STATE, 'a-verifier', new \DateTimeImmutable('2026-10-15 09:00:00 UTC'));

        self::getContainer()->get(AuthorizationRepository::class)->keep($asked);

        $item = $this->connectorsTable()->item(ConnectorsKeys::authorization(self::STATE), ConnectorsKeys::AUTHORIZATION);
        $this->assertEqualsWithDelta(new \DateTimeImmutable('+1 hour')->getTimestamp(), $item['expiresAt'], 60);
        $this->assertEquals($asked, self::getContainer()->get(AuthorizationRepository::class)->find(self::STATE));
    }

    private function connectorsTable(): Table
    {
        $table = self::getContainer()->get('app.table.connectors');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
