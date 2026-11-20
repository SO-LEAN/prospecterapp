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

        return $this->resultOfTheCall();
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
        $this->client->request('POST', '/mcp', server: [
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_MCP_PROTOCOL_VERSION' => self::REVISION_OF_THE_HANDSHAKE,
        ], content: json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], JSON_THROW_ON_ERROR));

        return $this->resultOfTheCall();
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
}
