<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

/**
 * The endpoint that an MCP tool adapts, by its route's name. Every member endpoint has its tool (ADR 0163,
 * `tests/Architecture/EveryEndpointHasItsToolTest`).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Adapts
{
    public function __construct(public string $route)
    {
    }
}
