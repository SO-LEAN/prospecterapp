<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * How what a member created came: by their own hand, or by an AI acting as them through the MCP server, never
 * named (ADR 0157).
 */
enum Origin: string
{
    case Hand = 'hand';
    case Ai = 'ai';

    public static function byAnAI(bool $byAnAI): self
    {
        return $byAnAI ? self::Ai : self::Hand;
    }
}
