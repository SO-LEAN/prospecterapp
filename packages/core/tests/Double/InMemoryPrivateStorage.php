<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\Port\Upload;
use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;

/**
 * Keeps the uploads the client sent and the files kept from them, like the private bucket of the storage: an
 * upload is kept once, a link reads a kept file only.
 */
final class InMemoryPrivateStorage implements PrivateStorage
{
    private const string LINKS = 'https://private.test/';

    /** @var array<string, array{contents: string, mediaType: string}> by upload */
    private array $uploads = [];
    /** @var array<string, string> contents by file */
    private array $kept = [];
    private int $sent = 0;

    /**
     * What the client does with the form the storage signed for it.
     */
    public function send(string $contents, string $mediaType): string
    {
        $upload = sprintf('%032x', ++$this->sent);
        $this->uploads[$upload] = ['contents' => $contents, 'mediaType' => $mediaType];

        return $upload;
    }

    public function uploaded(string $upload): Upload
    {
        $sent = $this->uploads[$upload] ?? throw UnknownUpload::identifiedBy($upload);

        return new Upload(strlen($sent['contents']), $sent['mediaType']);
    }

    public function keep(string $upload): void
    {
        $sent = $this->uploads[$upload] ?? throw UnknownUpload::identifiedBy($upload);
        unset($this->uploads[$upload]);
        $this->kept[$upload] = $sent['contents'];
    }

    public function remove(string $file): void
    {
        unset($this->kept[$file]);
    }

    public function downloadLinkOf(string $file, string $name): string
    {
        return sprintf('%s%s?name=%s', self::LINKS, $file, rawurlencode($name));
    }

    /**
     * What a link reads, null when the file is no longer kept.
     */
    public function read(string $link): ?string
    {
        $file = (string) parse_url(substr($link, strlen(self::LINKS)), PHP_URL_PATH);

        return $this->kept[$file] ?? null;
    }

    public function holds(string $file): bool
    {
        return isset($this->kept[$file]);
    }
}
