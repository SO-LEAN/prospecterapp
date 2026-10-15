<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\Storage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The storage app keeps the files (ADR 0033): the API sends them on its internal server, with the token of
 * the services of the platform, and keeps their public URL.
 */
final readonly class StorageClient implements Storage
{
    public function __construct(
        private HttpClientInterface $httpClient,
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

        return $this->httpClient->request('POST', $this->internalUrl.'/files', [
            'auth_bearer' => $this->token,
            'headers' => $form->getPreparedHeaders()->toArray(),
            'body' => $form->bodyToIterable(),
        ])->toArray()['url'];
    }

    public function remove(string $url): void
    {
        $this->httpClient->request('DELETE', sprintf('%s/files/%s', $this->internalUrl, $this->relativePathOf($url)), ['auth_bearer' => $this->token])->getStatusCode();
    }

    private function relativePathOf(string $url): string
    {
        return ltrim(substr($url, strlen($this->publicUrl)), '/');
    }
}
