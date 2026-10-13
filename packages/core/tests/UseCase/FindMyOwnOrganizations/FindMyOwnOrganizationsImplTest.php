<?php

declare( strict_types = 1 );

namespace Tests\Core\UseCase\FindMyOwnOrganizations;

use PHPUnit\Framework\Attributes\DataProvider;
use Prospecter\Core\Gateway\Entity\Page;
use Prospecter\Core\Gateway\Entity\PageRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Prospecter\Core\UseCase\UseCaseConsumer;
use Tests\Core\Base\UseCaseTestCase;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsImpl;
use Tests\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse\OrganizationBuilder;

use function Tests\Core\Base\aPage;
use function Tests\Core\Base\aFile;
use function Tests\Core\Base\aGeoPoint;
use function Tests\Core\Base\anAddress;
use function Tests\Core\Base\anOrganization;

class FindMyOwnOrganizationsImplTest extends UseCaseTestCase
{
    public function target() : FindMyOwnOrganizationsImpl
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return [
            $this->prophesy(OrganizationGateway::class)->reveal(),
        ];
    }

    public function testProspectorCanGetOrganization()
    {
        $this->assertContains('ROLE_PROSPECTOR', $this->target()->canBeExecutedBy());
    }

        #[DataProvider('provideExecute')]
    public function testExecute(FindMyOwnOrganizationsResponse $expectedResponse, Page $expectedPage)
    {
        $request = aRequest()
            ->build();

        $this->prophesy(UseCaseConsumer::class)
            ->getOrganizationId()
            ->shouldBeCalled()
            ->willReturn(222);

        $this->prophesy(OrganizationGateway::class)
            ->findPageByQuery(new PageRequest($request->getPage(), $request->getQuery(), $request->getMaxByPage(), ['ownedBy' => 222]))
            ->shouldBeCalled()
            ->willReturn($expectedPage);


        $result = $this->target()->execute($request, $this->getMockedPresenter($expectedResponse), $this->prophesy(UseCaseConsumer::class)->reveal());

        $this->assertEquals($expectedResponse, $result);
    }

    public static function provideExecute()
    {
        $resp = aResponse();
        $page = aPage();

        (yield 'default' => [
            $resp
                ->with('organizations', [
                    aDtoOrganization()
                        ->build()
                    ])
                ->build(),
            $page
                ->with('content', [
                    anOrganization()
                        ->withId()
                        ->build()
                    ])
                ->build()
        ]);
        (yield 'with address' => [
            $resp
                ->with('organizations', [
                    aDtoOrganization()
                        ->withAddress()
                        ->build()
                    ])
                ->build(),
            $page->with('content', [
                anOrganization()
                    ->withId()
                    ->with('address', anAddress())
                    ->build()
                ])
                ->build()
        ]);
        (yield 'with logo' => [
            $resp
                ->with('organizations', [
                    aDtoOrganization()
                        ->withLogo()
                        ->build()
                ])
                ->build(),
            $page->with('content', [
                anOrganization()
                    ->withId()
                    ->with('logo', aFile()->withImageData())
                    ->build()
            ])
                ->build()
        ]);
        (yield 'with geoPoint' => [
            $resp
                ->with('organizations', [
                    aDtoOrganization()
                        ->withGeoPoint()
                        ->build()
                ])
                ->build(),
            $page->with('content', [
                anOrganization()
                    ->withId()
                    ->with('geoPoint', aGeoPoint())
                    ->build()
            ])
                ->build()
        ]);
    }
}

function aRequest()
{
    return new FindMyOwnOrganizationsRequestBuilder();
}
function aResponse()
{
    return new FindMyOwnOrganizationsResponseBuilder();
}

function aDtoOrganization()
{
    return new OrganizationBuilder();
}
