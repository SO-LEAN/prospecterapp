<?php

namespace Tests\App\Shared;

use App\Shared\OnlyThroughTheEdge;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Behind an edge, the api answers only what the edge passes on.
 */
#[Group('unit')]
class OnlyThroughTheEdgeTest extends TestCase
{
    public function testARequestThroughTheEdgeGoesOn(): void
    {
        $event = $this->requestWith(['HTTP_X_ORIGIN_VERIFY' => 'the-secret']);
        new OnlyThroughTheEdge('the-secret')($event);

        $this->assertNull($event->getResponse());
    }

    public function testARequestWithoutTheSecretIsForbidden(): void
    {
        $event = $this->requestWith([]);
        new OnlyThroughTheEdge('the-secret')($event);

        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public function testARequestWithAnotherSecretIsForbidden(): void
    {
        $event = $this->requestWith(['HTTP_X_ORIGIN_VERIFY' => 'a-guess']);
        new OnlyThroughTheEdge('the-secret')($event);

        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public function testWithoutAnEdgeEveryRequestGoesOn(): void
    {
        $event = $this->requestWith([]);
        new OnlyThroughTheEdge('')($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @param array<string, string> $server
     */
    private function requestWith(array $server): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create('/api/me', server: $server), HttpKernelInterface::MAIN_REQUEST);
    }
}
