<?php

declare( strict_types = 1 );

namespace Tests\Unit\Solean\CleanProspecter\UseCase\UpdateMyAccountInformation;

use SplFileInfo;
use Solean\CleanProspecter\Entity\User;
use Tests\Unit\Solean\Base\UseCaseTestCase;
use Solean\CleanProspecter\Gateway\Storage;
use Solean\CleanProspecter\Exception\Gateway;
use Solean\CleanProspecter\Exception\UseCase;
use Solean\CleanProspecter\Entity\Organization;
use Solean\CleanProspecter\Gateway\UserNotifier;
use Solean\CleanProspecter\UseCase\UseCaseConsumer;
use Solean\CleanProspecter\Gateway\Entity\UserGateway;
use Solean\CleanProspecter\Gateway\Entity\Transaction;
use Solean\CleanProspecter\Gateway\Entity\OrganizationGateway;
use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationImpl;
use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationResponse;

use function Tests\Unit\Solean\Base\aUser;
use function Tests\Unit\Solean\Base\aFile;
use function Tests\Unit\Solean\Base\anOrganization;

class UpdateMyAccountInformationImplTest extends UseCaseTestCase
{
    public function target() : UpdateMyAccountInformationImpl
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return [
            $this->prophesy(OrganizationGateway::class)->reveal(),
            $this->prophesy(UserGateway::class)->reveal(),
            $this->prophesy(Transaction::class)->reveal(),
            $this->prophesy(Storage::class)->reveal(),
            $this->prophesy(UserNotifier::class)->reveal(),
        ];
    }

    public function testUserCanUpdateOrganization()
    {
        $this->assertContains('ROLE_USER', $this->target()->canBeExecutedBy());
    }

    public function testExecute()
    {
        $request = aRequest()->build();
        $persistedOrganization = anOrganization()
            ->withId()
            ->build();
        $persistedUser = aUser()
            ->withId()
            ->build();
        $expected = aResponse()->build();

        $this->mockTransaction();
        $this->mock($persistedOrganization, $persistedUser);
        /**
         * @var UpdateMyAccountInformationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expected), $this->prophesy(UseCaseConsumer::class)->reveal());

        $this->assertEquals($expected, $response);
    }

    public function testExecuteEmptyToFull()
    {
        $request = aRequest()
            ->withNewData()
            ->build();
        $persistedOrganization = anOrganization()
            ->withId()
            ->build();
        $alteredOrganization = anOrganization()
            ->withId()
            ->withNewDataFromAccountUpdate()
            ->build();

        $persistedUser = aUser()
            ->withId()
            ->build();
        $alteredUser= aUser()
            ->withId()
            ->withNewData()
            ->build();

        $expected = aResponse()
            ->withNewData()
            ->build();

        $this->mockTransaction();
        $this->mock($persistedOrganization, $persistedUser, $alteredOrganization, $alteredUser);
        /**
         * @var UpdateMyAccountInformationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expected), $this->prophesy(UseCaseConsumer::class)->reveal());

        $this->assertEquals($expected, $response);
    }

    public function testExecuteWithPicture()
    {
        $persistedOrganization = anOrganization()
            ->withId()
            ->build();

        $alteredOrganization = anOrganization()
            ->withId()
            ->with('language', 'FR')
            ->build();

        $persistedUser = aUser()
            ->withId()
            ->build();

        $alteredUser= aUser()
            ->withId()
            ->with('picture', aFile()->withImageData()->build())
            ->build();

        $expected = aResponse()
            ->withPicture()
            ->build();

        $file = $this->mockUploadedFile($alteredUser->getPicture()->getSize(), $alteredUser->getPicture()->getExtension(), $alteredUser->getPicture()->getUrl());

        $request = aRequest()
            ->withPicture($file)
            ->build();

        $this->mockTransaction();
        $this->mock($persistedOrganization, $persistedUser, $alteredOrganization, $alteredUser);
        /**
         * @var UpdateMyAccountInformationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expected), $this->prophesy(UseCaseConsumer::class)->reveal());

        $this->assertEquals($expected, $response);
    }

    public function testExecuteWithLogo()
    {
        $persistedOrganization = anOrganization()
            ->withId()
            ->build();

        $alteredOrganization = anOrganization()
            ->withId()
            ->with('language', 'FR')
            ->with('logo', aFile()->withImageData())
            ->build();

        $persistedUser = aUser()
            ->withId()
            ->build();

        $alteredUser= aUser()
            ->withId()
            ->build();

        $expected = aResponse()
            ->withOrganizationLogo()
            ->build();

        $file = $this->mockUploadedFile($alteredOrganization->getLogo()->getSize(), $alteredOrganization->getLogo()->getExtension(), $alteredOrganization->getLogo()->getUrl());

        $request = aRequest()
            ->withOrganizationLogo($file)
            ->build();

        $this->mockTransaction();
        $this->mock($persistedOrganization, $persistedUser, $alteredOrganization, $alteredUser);
        /**
         * @var UpdateMyAccountInformationResponse $response
         */
        $response = $this->target()->execute($request, $this->getMockedPresenter($expected), $this->prophesy(UseCaseConsumer::class)->reveal());

        $this->assertEquals($expected, $response);
    }

    public function testExecuteRollbackAndThrowExceptionOnUniqueConstraintViolationException()
    {
        $request = aRequest()
            ->build();
        $persistedOrganization = anOrganization()
            ->withId()
            ->missingMandatoryData()
            ->build();
        $persistedUser = aUser()
            ->withId()
            ->build();

        $this->prophesy(Transaction::class)->begin()->shouldBeCalled();
        $this->prophesy(Transaction::class)->rollback()->shouldBeCalled();
        $this->prophesy(UseCaseConsumer::class)
            ->getOrganizationId()
            ->willReturn(123);
        $this->prophesy(UseCaseConsumer::class)
            ->getUserId()
            ->shouldBeCalled()
            ->willReturn(123);
        $this->prophesy(OrganizationGateway::class)
            ->get(123)
            ->shouldBeCalled()
            ->willReturn($persistedOrganization);
        $this->prophesy(UserGateway::class)
            ->get(123)
            ->shouldBeCalled()
            ->willReturn($persistedUser);

        $this->prophesy(UserGateway::class)
            ->update(123, $persistedUser)
            ->shouldBeCalled()
            ->willThrow(new Gateway\UniqueConstraintViolationException());

        $this->expectExceptionObject(new UseCase\UniqueConstraintViolationException('Email already used', 412, new Gateway\UniqueConstraintViolationException()));


        $this->target()->execute($request, $this->getMockedPresenter(), $this->prophesy(UseCaseConsumer::class)->reveal());
    }

    private function mockTransaction()
    {
        $this->prophesy(Transaction::class)->begin()->shouldBeCalled();
        $this->prophesy(Transaction::class)->rollback()->shouldNotBeCalled();
        $this->prophesy(Transaction::class)->commit()->shouldBeCalled();
    }
    private function mock(Organization $persistedOrganization, User $persistedUser, ?Organization $alteredOrganization = null, ?User $alteredUser = null): void
    {
        $this->prophesy(UseCaseConsumer::class)
            ->getOrganizationId()
            ->willReturn(123);
        $this->prophesy(UseCaseConsumer::class)
            ->getUserId()
            ->shouldBeCalled()
            ->willReturn(123);
        $this->prophesy(OrganizationGateway::class)
            ->get(123)
            ->shouldBeCalled()
            ->willReturn($persistedOrganization);
        $this->prophesy(UserGateway::class)
            ->get(123)
            ->shouldBeCalled()
            ->willReturn($persistedUser);
        $this->prophesy(OrganizationGateway::class)
            ->update($persistedOrganization->getId(), $alteredOrganization ?? $persistedOrganization)
            ->shouldBeCalled()
            ->willReturn($alteredOrganization ?? $persistedOrganization);
        $this->prophesy(UserGateway::class)
            ->update($persistedOrganization->getId(), $alteredUser ?? $persistedUser)
            ->shouldBeCalled()
            ->willReturn($alteredUser ?? $persistedUser);
        $this->prophesy(UserNotifier::class)
            ->addSuccess('User account information updated !')
            ->shouldBeCalled();
    }


}

function aRequest()
{
    return new UpdateMyAccountInformationRequestBuilder();
}
function aResponse()
{
    return new UpdateMyAccountInformationResponseBuilder();
}
