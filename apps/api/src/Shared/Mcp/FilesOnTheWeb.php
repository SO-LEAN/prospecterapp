<?php

namespace App\Shared\Mcp;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The files an AI gives by their address on the web, downloaded in place of those the screen sends (ADR 0168): never
 * on a private network (the client given), in 5 seconds, without following a redirect, no heavier than the screen
 * takes; the file downloaded removed once used.
 */
final readonly class FilesOnTheWeb
{
    private const float SECONDS = 5.0;

    public function __construct(private HttpClientInterface $http)
    {
    }

    /**
     * @template TResult
     *
     * @param \Closure(File): TResult $use what is done with the file, in a file named with the extension of its type, kept by then (the commands run within the request)
     *
     * @return TResult
     *
     * @throws ValidationFailedException the file out of reach, or heavier than the bytes it may weigh
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
            throw new ValidationFailedException($address, new ConstraintViolationList([new ConstraintViolation(sprintf('The file could not be downloaded: %s', $failure->getMessage()), null, [], $address, 'url', $address->url)]));
        }
    }

    private static function stopBeyond(int $heaviest, int $bytes): null
    {
        return $bytes > $heaviest ? throw new TransportException(sprintf('the file is larger than %d bytes', $heaviest)) : null;
    }

    /**
     * The file named with the extension of its type, as the storage keeps it.
     */
    private static function named(File $file): File
    {
        $extension = $file->guessExtension();

        return null === $extension ? $file : $file->move(dirname($file->getPathname()), $file->getFilename().'.'.$extension);
    }
}
