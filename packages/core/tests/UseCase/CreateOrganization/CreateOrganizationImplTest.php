<?php

declare( strict_types = 1 );

namespace Tests\Core\UseCase\CreateOrganization;

use SplFileInfo;
use Prophecy\Argument;
use Tests\Core\Base\UseCaseTestCase;
use Prospecter\Core\Entity\GeoPoint;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase;
use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationImpl;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationResponse;

use function Tests\Core\Base\anOrganization;
use function Tests\Core\Base\aGeoPoint;
use function Tests\Core\Base\anAddress;
use function Tests\Core\Base\aFile;

class CreateOrganizationImplTest extends UseCaseTestCase
{
    public function target() : CreateOrganizationImpl
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return [
            $this->prophesy(OrganizationGateway::class)->reveal(),
            $this->prophesy(Storage::class)->reveal(),
            $this->prophesy(UserNotifier::class)->reveal(),
            $this->prophesy(GeoLocation::class)->reveal(),
        ];
    }

    public function testProspectorCanCreateOrganization()
    {
        $this->assertContains('ROLE_PROSPECTOR', $this->target()->canBeExecutedBy());
    }

    public function testExecuteOnRegularWithCreator()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->build();

        $organizationBuilder = anOrganization()
            ->ownedBy(anOrganization()->withCreatorData());

        $notPersisted = $organizationBuilder->build();
        $persisted =  $organizationBuilder->withId()->build();

        $expectedResponse = aResponse()->build();

        $this->mock($notPersisted, $persisted);

        /**
         * @var CreateOrganizationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expectedResponse));

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals(1, $persisted->getOwnedBy()->getStat('activeOrganizations'));
    }

    public function testExecuteOnRegularWithAddress()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->withAddress()
            ->build();

        $organizationBuilder = anOrganization()
            ->ownedBy(anOrganization()->withCreatorData())
            ->with('address', anAddress())
            ->with('geoPoint', $geoPoint = aGeoPoint())
        ;

        $notPersisted = $organizationBuilder->build();
        $persisted =  $organizationBuilder->withId()->build();

        $expectedResponse = aResponse()
            ->withRegularAddress()
            ->build();

        $this->mock($notPersisted, $persisted);
        $this->mockGeoLocation($geoPoint->build());

        /**
         * @var CreateOrganizationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expectedResponse));

        $this->assertEquals($expectedResponse, $response);
    }

    public function testExecuteOnRegularWithLogo()
    {
        $organizationBuilder = anOrganization()
            ->ownedBy(anOrganization()->withCreatorData())
            ->with('logo', aFile()->withImageData());

        $notPersisted = $organizationBuilder->build();
        $persisted =  $organizationBuilder->withId()->build();

        $file = $this->mockUploadedFile($notPersisted->getLogo()->getSize(), $notPersisted->getLogo()->getExtension(), $notPersisted->getLogo()->getUrl());

        $request = aRequest()
            ->ownedByCreator()
            ->withLogo($file)
            ->build();

        $expectedResponse = aResponse()
            ->withLogo()
            ->build();

        $this->mock($notPersisted, $persisted);

        /**
         * @var CreateOrganizationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expectedResponse));

        $this->assertEquals($expectedResponse, $response);
    }

    public function testExecuteOnHold()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->hold()
            ->build();

        $organizationBuilder = anOrganization()
            ->ownedBy(anOrganization()->withCreatorData())
            ->with('holdBy', anOrganization()->withHoldingData());

        $notPersisted = $organizationBuilder->build();
        $persisted =  $organizationBuilder->withId()->build();

        $expectedResponse = aResponse()
            ->hold()
            ->build();

        $this->mockGetHolding($request);
        $this->mock($notPersisted, $persisted);

        /**
         * @var CreateOrganizationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expectedResponse));

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThrowAnUseCaseNotFoundExceptionIfHoldingNotFoundInGatewayDuringExecuteOnHold()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->hold()
            ->build();

        $gatewayException = new Gateway\NotFoundException();

        $this->prophesy(OrganizationGateway::class)->get($request->getOwnedBy())->shouldBeCalled()->willReturn(anOrganization()->withCreatorData()->build());
        $this->prophesy(OrganizationGateway::class)->get($request->getHoldBy())->shouldBeCalled()->willThrow($gatewayException);
        $this->expectExceptionObject(new UseCase\NotFoundException(sprintf('Holding with #ID %d not found', $request->getHoldBy()), 404, $gatewayException));

        $this->target()->execute($request, $this->getMockedPresenter());
    }

    public function testThrowAnUseCaseUniqueConstraintViolationExceptionIfGatewayThrowOne()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->build();

        $notPersisted = anOrganization()
            ->ownedBy(anOrganization()->withCreatorData())
            ->build();

        $gatewayException = new Gateway\UniqueConstraintViolationException();

        $this->prophesy(OrganizationGateway::class)->get($request->getOwnedBy())->shouldBeCalled()->willReturn(anOrganization()->withCreatorData()->build());
        $this->prophesy(OrganizationGateway::class)->create($notPersisted)->shouldBeCalled()->willThrow($gatewayException);
        $this->expectExceptionObject(new UseCase\UniqueConstraintViolationException('Email already used', 412, $gatewayException));

        $this->target()->execute($request, $this->getMockedPresenter());
    }

    public function testThrowUseCaseExceptionIfMissingCorporateNameAndEmail()
    {
        $request = aRequest()
            ->ownedByCreator()
            ->missingMandatoryData()
            ->build();

        $this->mockGetCreator();
        $this->expectExceptionObject(new UseCase\UseCaseException('At least one is mandatory : corporate name or email', 412));

        $this->target()->execute($request, $this->getMockedPresenter());
    }

    public function testThrowUseCaseExceptionIfMissingOwner()
    {
        $request = aRequest()
            ->build();

        $this->expectExceptionObject(new UseCase\UseCaseException('Owner is missing', 412));

        $this->target()->execute($request, $this->getMockedPresenter());
    }

    private function mock(Organization $notPersisted, Organization $persisted): void
    {
        $this->mockGetCreator();
        $this->prophesy(OrganizationGateway::class)->create($notPersisted)->shouldBeCalled()->willReturn($persisted);
        $this->prophesy(UserNotifier::class)->addSuccess('Organization created !')->shouldBeCalled();
    }

    private function mockGetCreator(): void
    {
        $this->prophesy(OrganizationGateway::class)->get(777)->shouldBeCalled()->willReturn(anOrganization()->withCreatorData()->build());
    }



    private function mockGetHolding(CreateOrganizationRequest $request): void
    {
        $this->prophesy(OrganizationGateway::class)->get($request->getHoldBy())->shouldBeCalled()->willReturn(anOrganization()->withHoldingData()->build());
    }

    private function mockGeoLocation(GeoPoint $expectedPoint): void
    {
        $this->prophesy(GeoLocation::class)->find(Argument::type('string'))->shouldBeCalled()->willReturn(new GeoLocation\GeoPointResponse('address', $expectedPoint->getLongitude(), $expectedPoint->getLatitude(), true));
    }
}

function aRequest()
{
    return new CreateOrganizationRequestBuilder();
}
function aResponse()
{
    return new CreateOrganizationResponseBuilder();
}
