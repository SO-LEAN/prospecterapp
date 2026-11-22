<?php

declare(strict_types=1);

namespace Tests\App\Base;

/**
 * An AI writing through the MCP server as its member does on the screens (ADR 0163): each tool of a write does what
 * its endpoint does, the resource it answers that the screen reads then, or refuses as its endpoint refuses, to a
 * prospector, an assistant without the right to manage the personal data, an administrator, a member who is no
 * prospector and a member of another account alike.
 */
trait ActsAsItsEndpoint
{
    use CallsTheMcpServer;

    protected const array WRITERS = ['prospector-user', 'assistant-user', 'admin-user', 'member-user', 'stranger'];

    protected const array PROSPECTORS = ['prospector-user', 'assistant-user', 'admin-user'];

    protected const array PERSONAL_DATA_MANAGERS = ['prospector-user', 'admin-user'];

    /**
     * Each writer calls the tool: refused, the endpoint refuses them the same; done, by a writer the endpoint allows,
     * it answers the resource the screen then reads, by its link `self`.
     *
     * @param array<string, mixed>      $arguments
     * @param array<string, mixed>|null $payload
     * @param list<string>              $allowed   the writers the endpoint allows
     *
     * @return array<string, mixed> what the tool answered the first writer it allowed
     */
    private function assertTheToolActsAsItsEndpoint(string $tool, array $arguments, string $method, string $endpoint, ?array $payload, array $allowed): array
    {
        $done = [];
        foreach (self::WRITERS as $writer) {
            $this->login($writer);
            $result = $this->callWithoutHandshake($tool, $arguments);
            $answer = $this->answerOf($result);

            if (true === ($result['isError'] ?? false)) {
                $this->assertSame($this->requestJson($method, $endpoint, $payload), $answer, sprintf('%s refused to %s', $tool, $writer));
                continue;
            }

            $this->assertContains($writer, $allowed, sprintf('%s done by %s, whom its endpoint refuses', $tool, $writer));
            $this->assertSame($this->requestJson('GET', self::stringAt($answer, '_links', 'self', 'href')), $answer, sprintf('%s done by %s', $tool, $writer));
            $done[] = $answer;
        }

        return $done[0] ?? throw new \LogicException(sprintf('%s done by nobody', $tool));
    }

    /**
     * Each of these writers calls the tool, refused as the endpoint refuses them.
     *
     * @param array<string, mixed>      $arguments
     * @param array<string, mixed>|null $payload
     * @param list<string>              $refused
     */
    private function assertTheToolIsRefusedAsItsEndpoint(string $tool, array $arguments, string $method, string $endpoint, ?array $payload, array $refused): void
    {
        foreach ($refused as $writer) {
            $this->login($writer);
            $result = $this->callWithoutHandshake($tool, $arguments);

            $this->assertTrue($result['isError'] ?? false, sprintf('%s done by %s', $tool, $writer));
            $this->assertSame($this->requestJson($method, $endpoint, $payload), $this->answerOf($result), sprintf('%s refused to %s', $tool, $writer));
        }
    }

    /**
     * The tool says it removes or erases: its AI asks its member before calling it.
     */
    private function assertTheToolAsksForAConfirmation(string $tool): void
    {
        $this->login();
        $listed = array_find($this->everyToolListed(), static fn (array $listed): bool => $tool === $listed['name']) ?? throw new \LogicException(sprintf('%s is not listed', $tool));

        $this->assertSame(['readOnlyHint' => false, 'destructiveHint' => true], array_intersect_key(self::recordAt($listed, 'annotations'), ['readOnlyHint' => true, 'destructiveHint' => true]));
    }
}
