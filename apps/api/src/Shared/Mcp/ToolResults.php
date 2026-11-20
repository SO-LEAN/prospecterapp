<?php

namespace App\Shared\Mcp;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * What a tool of the MCP server answers: the resource of the endpoint it adapts, serialized as the endpoint
 * serializes it, its links included, for the AI to go from one resource to another (ADR 0163).
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
        return json_decode($this->serializer->serialize($resource, 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS]), true, flags: JSON_THROW_ON_ERROR);
    }
}
