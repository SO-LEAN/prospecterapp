<?php

namespace App\Shared\Mcp;

/**
 * The endpoint a tool of the MCP server adapts, by the name of its route: every endpoint of a member has its tool
 * (ADR 0163, `tests/Architecture/EveryEndpointHasItsToolTest`).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Adapts
{
    public function __construct(public string $route)
    {
    }
}
