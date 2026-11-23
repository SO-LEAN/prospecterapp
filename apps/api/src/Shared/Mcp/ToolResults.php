<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use App\Shared\Typed;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * What an MCP tool answers: the resource of the endpoint that the tool adapts. It is serialized as the endpoint
 * serializes it, links included, so the AI can go from one resource to another (ADR 0163).
 */
final readonly class ToolResults
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function of(object $resource): array
    {
        return Typed::document(json_decode($this->serializer->serialize($resource, 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS]), true, flags: JSON_THROW_ON_ERROR));
    }
}
