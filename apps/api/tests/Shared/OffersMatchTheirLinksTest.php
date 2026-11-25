<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use App\Shared\Typed;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * In the tests, a links resource is serialized only when each link it carries leads to the route its property
 * declares it offers, and when each link to a controller that sends a command is declared (docs/architecture/api.md,
 * "Links").
 */
final class OffersMatchTheirLinksTest extends KernelTestCase
{
    private const string CAMPAIGN_ID = '0195d1c8-1f2a-7000-8000-000000000001';

    protected function setUp(): void
    {
        Typed::object(self::getContainer()->get('router'), RouterInterface::class)->getContext()->setHost(Typed::string(self::getContainer()->getParameter('app_api_host')));
    }

    public function testALinkLeadingToAnotherRouteThanItsDeclarationFails(): void
    {
        $this->expectExceptionObject(new \LogicException(sprintf('%s::$revise declares it offers api_campaigns_revise, but its link leads to api_campaigns_freeze', CampaignLinksFixture::class)));

        $this->serialize(new CampaignLinksFixture(revise: $this->linkTo('api_campaigns_freeze')));
    }

    public function testALinkToAControllerSendingACommandWithoutADeclarationFails(): void
    {
        $this->expectExceptionObject(new \LogicException(sprintf('%s::$freeze leads to api_campaigns_freeze, whose controller sends a command: declare it with #[Offers(\'api_campaigns_freeze\')]', CampaignLinksFixture::class)));

        $this->serialize(new CampaignLinksFixture(freeze: $this->linkTo('api_campaigns_freeze')));
    }

    public function testADeclaredLinkLeadingToItsRouteIsSerialized(): void
    {
        $this->assertStringContainsString('"revise":{"href":"\/api\/campaigns\/'.self::CAMPAIGN_ID.'","method":"PATCH"}', $this->serialize(new CampaignLinksFixture(revise: $this->linkTo('api_campaigns_revise'))));
    }

    public function testALinkLeadingToOneOfTheRoutesItsPropertyDeclaresIsSerialized(): void
    {
        $this->assertStringContainsString('"startOrStop":{"href":"\/api\/campaigns\/'.self::CAMPAIGN_ID.'\/stop","method":"POST"}', $this->serialize(new CampaignLinksFixture(startOrStop: $this->linkTo('api_campaigns_stop'))));
    }

    public function testALinkLeadingToNoneOfTheRoutesItsPropertyDeclaresFails(): void
    {
        $this->expectExceptionObject(new \LogicException(sprintf('%s::$startOrStop declares it offers api_campaigns_start, api_campaigns_stop, but its link leads to api_campaigns_freeze', CampaignLinksFixture::class)));

        $this->serialize(new CampaignLinksFixture(startOrStop: $this->linkTo('api_campaigns_freeze')));
    }

    public function testALinkToAControllerSendingNoCommandNeedsNoDeclaration(): void
    {
        $this->assertStringContainsString('"recipients":{"href"', $this->serialize(new CampaignLinksFixture(recipients: $this->linkTo('api_campaigns_recipients'))));
    }

    public function testALinkOutsideTheApiNeedsNoDeclaration(): void
    {
        $this->assertStringContainsString('"elsewhere":{"href":"https:\/\/mail.google.com\/mail\/#all\/1"', $this->serialize(new CampaignLinksFixture(elsewhere: new LinkResource('https://mail.google.com/mail/#all/1', 'GET'))));
    }

    public function testADeclaredLinkOutsideTheApiFails(): void
    {
        $this->expectExceptionObject(new \LogicException(sprintf('%s::$revise declares it offers api_campaigns_revise, but its link leads to no route', CampaignLinksFixture::class)));

        $this->serialize(new CampaignLinksFixture(revise: new LinkResource('https://mail.google.com/mail/#all/1', 'GET')));
    }

    private function linkTo(string $route): LinkResource
    {
        return Typed::object(self::getContainer()->get(Links::class), Links::class)->to($route, ['id' => self::CAMPAIGN_ID]);
    }

    private function serialize(object $resource): string
    {
        return Typed::object(self::getContainer()->get('serializer'), SerializerInterface::class)->serialize($resource, 'json');
    }
}
