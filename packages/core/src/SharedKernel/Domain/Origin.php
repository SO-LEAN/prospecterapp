<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * How a member created something: by hand, or through an AI acting as them on the MCP server. The AI is never
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
