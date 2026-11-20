<?php

namespace Tests\App\Architecture;

use App\Shared\Mcp\Adapts;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The MCP server never lags behind the application (ADR 0163): every endpoint a member calls, a route whose controller
 * declares `#[IsGranted]`, has its tool, the tool naming it by `#[Adapts]`, but those kept on the screen and those the
 * tickets after the first still have to cover.
 */
class EveryEndpointHasItsToolTest extends KernelTestCase
{
    /**
     * Kept on the screen: the forgetting of a contact (GDPR), the credentials of the sign-in, the connection of a
     * connector, its consent given to Google in a browser.
     */
    private const array NEVER = [
        'api_contacts_forget',
        'api_me_sign_in',
        'api_me_change_login_email',
        'api_me_change_password',
        'api_me_connections_google_authorize',
        'api_me_connections_google_connect',
        'api_me_connections_google_import_gmail',
    ];

    /**
     * The writes, which the tickets after the reads cover, the last of them removing this list.
     */
    private const array TO_COVER = [
        'api_booking_page_add_inactive_period',
        'api_booking_page_change_link',
        'api_booking_page_close',
        'api_booking_page_offer_channels',
        'api_booking_page_open',
        'api_booking_page_remove_inactive_period',
        'api_booking_page_set_windows',
        'api_campaigns_add_recipient',
        'api_campaigns_add_recipients_by_tags',
        'api_campaigns_discard',
        'api_campaigns_dismiss_recipient',
        'api_campaigns_freeze',
        'api_campaigns_note_exchange',
        'api_campaigns_prepare',
        'api_campaigns_remove_recipient',
        'api_campaigns_revise',
        'api_campaigns_start',
        'api_campaigns_stop',
        'api_contacts_collect',
        'api_contacts_note_to_do',
        'api_contacts_plan_exchange',
        'api_contacts_tag',
        'api_contacts_take_off_tag',
        'api_exchanges_attach_file',
        'api_exchanges_cancel',
        'api_exchanges_correct',
        'api_exchanges_correct_text',
        'api_exchanges_correct_venue',
        'api_exchanges_erase',
        'api_exchanges_google_sync_retry',
        'api_exchanges_mark_done',
        'api_exchanges_mark_missed',
        'api_exchanges_move',
        'api_exchanges_remove_attachment',
        'api_me_change_contact_details',
        'api_me_change_name',
        'api_me_change_organization_logo',
        'api_me_change_picture',
        'api_me_change_reminders',
        'api_me_configure_gmail_notifications',
        'api_me_configure_google_client',
        'api_me_connections_check',
        'api_me_connections_google_disconnect',
        'api_me_connections_google_retry_failed',
        'api_me_connections_google_stop_gmail_import',
        'api_me_relocate_organization',
        'api_me_remove_gmail_notifications',
        'api_me_remove_google_client',
        'api_me_revise_organization_identity',
        'api_organizations_add_contact',
        'api_organizations_attach_to_holding',
        'api_organizations_change_contact_details',
        'api_organizations_change_logo',
        'api_organizations_detach_from_holding',
        'api_organizations_dismiss_possible_duplicate',
        'api_organizations_note_to_do',
        'api_organizations_plan_exchange',
        'api_organizations_relocate',
        'api_organizations_remove_contact',
        'api_organizations_remove_logo',
        'api_organizations_revise_legal_identity',
        'api_organizations_start_prospecting',
        'api_organizations_stop_prospecting',
        'api_organizations_tag',
        'api_organizations_take_off_tag',
        'api_organizations_write_observations',
        'api_to_dos_erase',
        'api_to_dos_revise',
        'api_to_dos_tick_off',
        'api_to_dos_untick',
        'api_uploads_prepare',
    ];

    public function testEveryEndpointOfAMemberHasItsTool(): void
    {
        $withoutTool = array_diff(array_keys($this->endpointsOfTheMembers()), array_keys($this->adaptedByATool()), self::NEVER, self::TO_COVER);

        $this->assertSame([], array_values(array_map($this->named(...), $withoutTool)), 'Endpoints without a tool of the MCP server, nor in NEVER or TO_COVER');
    }

    public function testEveryToolOfAReadingSaysItChangesNothing(): void
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $readings = array_filter($this->adaptedByATool(), static fn (string $tool, string $route): bool => ['GET'] === $routes->get($route)?->getMethods(), ARRAY_FILTER_USE_BOTH);

        $this->assertSame([], array_values(array_filter($readings, static fn (string $tool): bool => true !== self::toolOf($tool)->annotations?->readOnlyHint)), 'Tools of a reading without the annotation readOnlyHint');
    }

    public function testEveryEndpointNamedApartExists(): void
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection();

        $this->assertSame([], array_values(array_filter([...array_keys($this->adaptedByATool()), ...self::NEVER, ...self::TO_COVER], static fn (string $route): bool => null === $routes->get($route))), 'Routes named by #[Adapts], NEVER or TO_COVER that no longer exist');
    }

    /**
     * @param class-string $tool
     */
    private static function toolOf(string $tool): McpTool
    {
        return new \ReflectionMethod($tool, '__invoke')->getAttributes(McpTool::class)[0]->newInstance();
    }

    /**
     * @return array<string, Route> by name
     */
    private function endpointsOfTheMembers(): array
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection()->all();

        return array_filter($routes, static fn (Route $route): bool => self::isGranted((string) $route->getDefault('_controller')));
    }

    private static function isGranted(string $controller): bool
    {
        if (!str_starts_with($controller, 'App\\')) {
            return false;
        }

        $class = new \ReflectionClass(explode('::', $controller)[0]);

        return [] !== $class->getAttributes(IsGranted::class) || [] !== $class->getMethod('__invoke')->getAttributes(IsGranted::class);
    }

    /**
     * @return array<string, class-string> the tools by the name of the route they adapt
     */
    private function adaptedByATool(): array
    {
        $tools = [];
        foreach ($this->tools() as $tool) {
            foreach ($tool->getAttributes(Adapts::class) as $adapts) {
                $tools[$adapts->newInstance()->route] = $tool->getName();
            }
        }

        return $tools;
    }

    /**
     * @return list<\ReflectionClass<object>>
     */
    private function tools(): array
    {
        $tools = [];
        foreach (glob(self::$kernel->getProjectDir().'/src/*/Mcp/*Tool.php') ?: [] as $file) {
            $class = new \ReflectionClass('App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen(self::$kernel->getProjectDir().'/src/'))));
            if ([] !== $class->getMethod('__invoke')->getAttributes(McpTool::class)) {
                $tools[] = $class;
            }
        }

        return $tools;
    }

    private function named(string $routeName): string
    {
        $route = self::getContainer()->get(RouterInterface::class)->getRouteCollection()->get($routeName);

        return sprintf('%s %s (%s)', implode('|', $route?->getMethods() ?? []), $route?->getPath(), $routeName);
    }
}
