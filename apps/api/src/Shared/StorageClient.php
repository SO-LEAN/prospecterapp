<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\Storage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The storage app keeps the files (ADR 0033): the API sends them on its internal entry, with the token of the
 * services of the platform and, on AWS, the signature of its role (ADR 0068), and keeps their public URL.
 */
final readonly class StorageClient implements Storage
{
    private const string TOKEN_HEADER = 'X-Storage-Token';

    public function __construct(
        private HttpClientInterface $httpClient,
        private StorageAuthentication $authentication,
        #[Autowire(env: 'STORAGE_INTERNAL_URL')]
        private string $internalUrl,
        #[Autowire(env: 'STORAGE_PUBLIC_URL')]
        private string $publicUrl,
        #[Autowire(env: 'STORAGE_TOKEN')]
        private string $token,
    ) {
    }

    public function add(\SplFileInfo $file): string
    {
        $form = new FormDataPart(['file' => DataPart::fromPath($file->getPathname(), sprintf('upload.%s', $file->getExtension()))]);
        $contentType = $form->getPreparedHeaders()->get('Content-Type')?->getBodyAsString() ?? '';

        return $this->send('POST', $this->internalUrl.'/files', ['Content-Type' => $contentType], $form->bodyToString())->toArray()['url'];
    }

    public function remove(string $url): void
    {
        $this->send('DELETE', sprintf('%s/files/%s', $this->internalUrl, $this->relativePathOf($url)))->getStatusCode();
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
    {
        $headers = $this->authentication->headersFor($method, $url, [...$headers, self::TOKEN_HEADER => $this->token], $body);

        return $this->httpClient->request($method, $url, ['headers' => $headers, 'body' => $body]);
    }

    private function relativePathOf(string $url): string
    {
        return ltrim(substr($url, strlen($this->publicUrl)), '/');
    }
}
