<?php

namespace Tests\App\Base;

/**
 * An AI calling the MCP server (ADR 0156), in either revision.
 */
trait CallsTheMcpServer
{
    private const string REVISION_WITHOUT_HANDSHAKE = '2026-07-28';

    private const string REVISION_OF_THE_HANDSHAKE = '2025-11-25';

    /**
     * A tool called in the revision without handshake, its version and the capabilities of the client in its
     * `_meta`, the headers mirroring its body.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed> the result of the tool
     */
    protected function callWithoutHandshake(string $tool, array $arguments = []): array
    {
        $this->sendWithoutHandshake($tool, $arguments);

        return $this->resultOfTheCall();
    }

    /**
     * The request of a tool in the revision without handshake, its answer left to read: a result, or the error of the
     * protocol for arguments its schema refuses.
     *
     * @param array<string, mixed> $arguments
     */
    protected function sendWithoutHandshake(string $tool, array $arguments = []): void
    {
        $this->client->request('POST', '/mcp', server: [
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_MCP_PROTOCOL_VERSION' => self::REVISION_WITHOUT_HANDSHAKE,
            'HTTP_MCP_METHOD' => 'tools/call',
            'HTTP_MCP_NAME' => $tool,
        ], content: json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $tool,
                'arguments' => (object) $arguments,
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => self::REVISION_WITHOUT_HANDSHAKE,
                    'io.modelcontextprotocol/clientCapabilities' => new \stdClass(),
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $this->assertResponseNotHasHeader('Mcp-Session-Id');
    }

    /**
     * A request of the revision of the handshake, its version in its header once initialized.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed> its result
     */
    protected function callWithHandshake(string $method, array $params): array
    {
        $this->sendWithHandshake($method, $params);

        return $this->resultOfTheCall();
    }

    /**
     * A request of the revision of the handshake, its answer left to read: a result, or the error of the protocol.
     *
     * @param array<string, mixed> $params
     */
    protected function sendWithHandshake(string $method, array $params): void
    {
        $this->client->request('POST', '/mcp', server: [
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_MCP_PROTOCOL_VERSION' => self::REVISION_OF_THE_HANDSHAKE,
        ], content: json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], JSON_THROW_ON_ERROR));
    }

    /**
     * A prompt as the server lists it, its arguments included.
     *
     * @return array<string, mixed>
     */
    protected function promptListed(string $name): array
    {
        $prompts = $this->callWithHandshake('prompts/list', [])['prompts'];

        return array_find($prompts, static fn (array $prompt): bool => $name === $prompt['name']) ?? throw new \LogicException(sprintf('No prompt "%s" is listed.', $name));
    }

    /**
     * @param array<string, mixed> $prompt
     *
     * @return list<array{string, bool}> the name of each argument and whether it is required
     */
    protected static function argumentsOf(array $prompt): array
    {
        return array_map(static fn (array $argument): array => [$argument['name'], $argument['required'] ?? false], $prompt['arguments'] ?? []);
    }

    /**
     * The text of a prompt got with its arguments.
     *
     * @param array<string, string> $arguments
     */
    protected function promptText(string $name, array $arguments = []): string
    {
        $messages = $this->callWithHandshake('prompts/get', ['name' => $name, 'arguments' => (object) $arguments])['messages'];

        return implode("\n", array_map(static fn (array $message): string => $message['content']['text'], $messages));
    }

    /**
     * The tools a prompt names, `identify` and those quoted in kebab-case (`log-contact-exchange`).
     *
     * @return list<string>
     */
    protected static function toolsNamedIn(string $text): array
    {
        preg_match_all('/`(identify|[a-z]+(?:-[a-z]+)+)`/', $text, $names);

        return array_values(array_unique($names[1]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function resultOfTheCall(): array
    {
        $this->assertResponseIsSuccessful();

        return $this->responseData()['result'] ?? throw new \LogicException((string) $this->client->getResponse()->getContent());
    }

    /**
     * What the tool answered: its result, or the problem of its refusal.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    protected function answerOf(array $result): array
    {
        return true === ($result['isError'] ?? false)
            ? json_decode($result['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR)
            : $result['structuredContent'];
    }

    /**
     * The tools an AI lists to the member signed in, page after page.
     *
     * @return list<array<string, mixed>>
     */
    protected function everyToolListed(): array
    {
        $tools = [];
        $cursor = [];
        do {
            $page = $this->callWithHandshake('tools/list', $cursor);
            $tools = [...$tools, ...$page['tools']];
            $cursor = isset($page['nextCursor']) ? ['cursor' => $page['nextCursor']] : null;
        } while (null !== $cursor);

        return $tools;
    }
}
