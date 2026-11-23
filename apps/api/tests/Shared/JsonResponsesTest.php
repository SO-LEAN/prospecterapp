<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\JsonResponses;
use App\Shared\Typed;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

final class JsonResponsesTest extends KernelTestCase
{
    public function testAResourceIsWrittenByTheSerializerWithTheEncodingOfAJsonResponse(): void
    {
        $resource = new class {
            public \DateTimeImmutable $at;
            public Channel $channel = Channel::VideoCall;
            public string $name = '<Hélène/>';

            public function __construct()
            {
                $this->at = new \DateTimeImmutable('2026-11-23T10:00:00+01:00');
            }
        };

        $this->assertSame(
            '{"at":"2026-11-23T10:00:00+01:00","channel":"videoCall","name":"\u003CH\u00e9l\u00e8ne\/\u003E"}',
            $this->jsonResponses()->of($resource)->getContent(),
        );
    }

    public function testANewResourceIsAnsweredWithItsStatusAndItsLocation(): void
    {
        $response = $this->jsonResponses()->of(['id' => 'a'], Response::HTTP_CREATED, ['Location' => '/api/to-dos/a']);

        $this->assertSame([Response::HTTP_CREATED, '/api/to-dos/a'], [$response->getStatusCode(), $response->headers->get('Location')]);
    }

    private function jsonResponses(): JsonResponses
    {
        return new JsonResponses(Typed::object(self::getContainer()->get('serializer'), SerializerInterface::class));
    }
}
