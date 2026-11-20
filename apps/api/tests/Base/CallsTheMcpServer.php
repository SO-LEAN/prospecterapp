<?php

namespace Tests\App\Base;

/**
 * An AI calling a tool of the MCP server (ADR 0156), in the revision without handshake.
 */
trait CallsTheMcpServer
{
    private const string REVISION_WITHOUT_HANDSHAKE = '2026-07-28';

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
     * @return array<string, mixed>
     */
    protected function resultOfTheCall(): array
    {
        $this->assertResponseIsSuccessful();

        return $this->responseData()['result'] ?? throw new \LogicException((string) $this->client->getResponse()->getContent());
    }
}
