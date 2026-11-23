<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The files an AI gives by their web address. They are downloaded in place of the files the screen sends (ADR 0168).
 * The download never reaches a private network (the given client sees to that). It lasts 5 seconds at most, follows no
 * redirect, and stops at the size the screen accepts. The downloaded file is removed once used.
 */
final readonly class FilesOnTheWeb
{
    private const float SECONDS = 5.0;

    /** Unauthorized and forbidden: what a file served only to signed-in users answers the others. */
    private const array SIGN_IN_REQUIRED = [401, 403];

    public function __construct(private HttpClientInterface $http)
    {
    }

    /**
     * @template TResult
     *
     * @param \Closure(File): TResult $use what to do with the file, which is named with its type's extension and kept until then (the commands run within the request)
     *
     * @return TResult
     *
     * @throws ValidationFailedException when the file is unreachable, or larger than the allowed bytes
     */
    public function whileDownloaded(WebAddressInput $address, int $heaviest, \Closure $use): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'download') ?: throw new \RuntimeException('No temporary file for '.$address->url);
        $named = $path;

        try {
            file_put_contents($path, $this->contentOf($address, $heaviest));
            $file = self::named(new File($path));
            $named = $file->getPathname();

            return $use($file);
        } finally {
            array_map(unlink(...), array_filter(array_unique([$path, $named]), is_file(...)));
        }
    }

    private function contentOf(WebAddressInput $address, int $heaviest): string
    {
        try {
            return $this->http->request('GET', $address->url, [
                'max_redirects' => 0,
                'timeout' => self::SECONDS,
                'max_duration' => self::SECONDS,
                'on_progress' => static fn (int $downloaded, int $size): null => self::stopBeyond($heaviest, max($downloaded, $size)),
            ])->getContent();
        } catch (ExceptionInterface $failure) {
            throw new ValidationFailedException($address, new ConstraintViolationList([new ConstraintViolation(self::why($failure), null, [], $address, 'url', $address->url)]));
        }
    }

    /**
     * Why the download failed. It says so when the file is behind a sign-in, or behind a redirect, which may lead to
     * one. The AI then knows to give an address served to anyone, because Prospecter signs in nowhere for its member.
     */
    private static function why(ExceptionInterface $failure): string
    {
        return match (true) {
            $failure instanceof ClientExceptionInterface && in_array($failure->getResponse()->getStatusCode(), self::SIGN_IN_REQUIRED, true) => 'The file could not be downloaded: it is behind a sign-in (an e-mail of a mailbox, a private drive…), which Prospecter does not pass; give the address of a file served to anyone.',
            $failure instanceof RedirectionExceptionInterface => 'The file could not be downloaded: its address redirects, which Prospecter does not follow, to a sign-in page perhaps (a private drive…); give the address the file itself is served to anyone at.',
            default => sprintf('The file could not be downloaded: %s', $failure->getMessage()),
        };
    }

    private static function stopBeyond(int $heaviest, int $bytes): null
    {
        return $bytes > $heaviest ? throw new TransportException(sprintf('the file is larger than %d bytes', $heaviest)) : null;
    }

    /**
     * Names the file with its type's extension, as the storage keeps it.
     */
    private static function named(File $file): File
    {
        $extension = $file->guessExtension();

        return null === $extension ? $file : $file->move(dirname($file->getPathname()), $file->getFilename().'.'.$extension);
    }
}
