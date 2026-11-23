<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\Prospecting\Application\Port\Upload;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownUpload;

/**
 * Keeps the uploads that the client sent and the files kept from them, like the storage's private bucket. An upload
 * is kept once. A link reads only a kept file.
 */
final class InMemoryPrivateStorage implements PrivateStorage
{
    private const string LINKS = 'https://private.test/';

    /** @var array<array{contents: string, mediaType: string}> by upload */
    private array $uploads = [];
    /** @var array<string, string> contents by file */
    private array $kept = [];
    private int $sent = 0;
    private bool $forgetsTheUploads = false;

    /**
     * Does what the client does with the form that the storage signed for it.
     */
    public function send(string $contents, string $mediaType): string
    {
        $upload = sprintf('%032x', ++$this->sent);
        if (!$this->forgetsTheUploads) {
            $this->uploads[$upload] = ['contents' => $contents, 'mediaType' => $mediaType];
        }

        return $upload;
    }

    /**
     * An upload that is never kept disappears after a day. A delivery that is retried later finds it gone.
     */
    public function forgetTheUploads(): void
    {
        $this->forgetsTheUploads = true;
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
     * What a link reads, or null when the file is no longer kept.
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
