<?php

declare(strict_types=1);

namespace Tests\App\Reporting;

use App\Demo\DemoData;
use App\Shared\Typed;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Tests\App\Base\ApiTestCase;

/**
 * The subscription `reporting-figures` keeps the figures of an account as its members prospect, from the facts
 * Prospecting and PersonalData publish.
 */
class FiguresFollowTheGesturesTest extends ApiTestCase
{
    public function testTheFiguresOfAnAccountFollowItsGestures(): void
    {
        self::service(RebuildFiguresHandler::class)(new RebuildFigures());
        $this->login();

        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $contact = Typed::string($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Grace'], 'organizationIds' => [$organization]])['id']);
        $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);

        $this->assertEquals(new FiguresResponse(activeOrganizations: 2, locatedOrganizations: 1, contacts: 2, exchanges: 3, exchangesThisMonth: 3), $this->figures());
    }

    private function figures(): FiguresResponse
    {
        $version = (int) self::service(ProjectionVersions::class)->active();

        return self::service(FiguresRetriever::class)->of($version, new AccountId(DemoData::SOLEAN_IT), Month::of(new \DateTimeImmutable('2026-10-15 09:00:00 UTC')));
    }
}
