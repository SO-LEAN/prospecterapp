<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use App\Shared\Images;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The images an AI gives by their address on the web, downloaded as the screen sends them (ADR 0168): 2 MB at most,
 * a JPEG, PNG or WebP, refused as the image of the screen otherwise.
 */
final readonly class ImagesOnTheWeb
{
    public function __construct(private FilesOnTheWeb $files, private ValidatorInterface $validator)
    {
    }

    /**
     * @template TResult
     *
     * @param \Closure(File): TResult $use what is done with the image, kept by then (the commands run within the request)
     *
     * @return TResult
     *
     * @throws ValidationFailedException the image out of reach, or no image Prospecter takes
     */
    public function whileDownloaded(WebAddressInput $address, \Closure $use): mixed
    {
        return $this->files->whileDownloaded($address, Images::LARGEST_BYTES, fn (File $image): mixed => $use($this->valid($image)));
    }

    private function valid(File $image): File
    {
        $violations = $this->validator->validate($image, Images::constraint());

        if (0 < $violations->count()) {
            throw new ValidationFailedException($image, $violations);
        }

        return $image;
    }
}
