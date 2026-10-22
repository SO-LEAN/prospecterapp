<?php

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Port\Storage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

/**
 * The storage app keeps the files (ADR 0033): the API sends them on its internal entry and keeps their public URL.
 */
final readonly class StorageClient implements Storage
{
    public function __construct(
        private StorageEntry $entry,
        #[Autowire(env: 'STORAGE_PUBLIC_URL')]
        private string $publicUrl,
    ) {
    }

    public function add(\SplFileInfo $file): string
    {
        $form = new FormDataPart(['file' => DataPart::fromPath($file->getPathname(), sprintf('upload.%s', $file->getExtension()))]);
        $contentType = $form->getPreparedHeaders()->get('Content-Type')?->getBodyAsString() ?? '';

        return $this->entry->send('POST', '/files', ['Content-Type' => $contentType], $form->bodyToString())->toArray()['url'];
    }

    public function remove(string $url): void
    {
        $this->entry->send('DELETE', '/files/'.$this->relativePathOf($url))->getStatusCode();
    }

    private function relativePathOf(string $url): string
    {
        return ltrim(substr($url, strlen($this->publicUrl)), '/');
    }
}
