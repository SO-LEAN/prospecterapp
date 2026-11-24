<?php

declare(strict_types=1);

namespace Tests\App\Base;

/**
 * An AI writing through the MCP server as its member does on the screens (ADR 0163). A write tool's arguments become
 * its endpoint's input, and the tool answers the resource that the screen then reads. Its rights and its command are
 * its controller's (`Tests\App\Architecture\EveryToolIsWiredAsItsControllerTest`), so a prospector calls each tool
 * once.
 */
trait ActsAsItsEndpoint
{
    use CallsTheMcpServer;

    /**
     * A prospector calls the tool. It answers the resource that the screen then reads, by its link `self`.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed> what the tool answered
     */
    private function assertTheToolActsAsItsEndpoint(string $tool, array $arguments): array
    {
        $this->login();
        $answer = $this->answerOf($this->callWithoutHandshake($tool, $arguments));

        $this->assertSame($this->requestJson('GET', self::stringAt($answer, '_links', 'self', 'href')), $answer, $tool);

        return $answer;
    }
}
