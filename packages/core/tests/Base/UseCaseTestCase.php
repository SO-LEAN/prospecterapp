<?php

declare( strict_types = 1 );

namespace Tests\Core\Base;

use Prophecy\Argument;
use RuntimeException;
use Prospecter\Core\Gateway\Storage;
use SplFileInfo;

abstract class UseCaseTestCase extends TestCase
{
    protected function getMockedPresenter($expected = null)
    {
        if (!$expected) {
            $this
                ->prophesy($this->getPresenterClassName())
                ->present(Argument::any())
                ->shouldNotBeCalled();
        } else {
            $this
                ->prophesy($this->getPresenterClassName())
                ->present($expected)
                ->shouldBeCalled()
                ->willReturnArgument(0);
        }

        return $this->prophesy($this->getPresenterClassName())->reveal();
    }

    protected function getPresenterClassName() : string
    {
        return sprintf('Prospecter\\%sPresenter', preg_replace('/(^Tests\\\)|(ImplTest$)/', '', get_class($this)));
    }

    /**
     * Uploaded file that Storage::add() moves away, like the real local storage does:
     * reading it after it has been stored fails.
     */
    protected function mockUploadedFile(int $size, string $extension, string $storedUrl): SplFileInfo
    {
        $moved = false;
        $file = $this->prophesy(SplFileInfo::class);

        $readBeforeMove = function (mixed $value) use (&$moved): mixed {
            if ($moved) {
                throw new RuntimeException('stat failed: the uploaded file was already moved by Storage::add()');
            }

            return $value;
        };

        $file->getSize()->shouldBeCalled()->will(fn (): int => $readBeforeMove($size));
        $file->getExtension()->shouldBeCalled()->will(fn (): string => $readBeforeMove($extension));

        $this->prophesy(Storage::class)->add($file->reveal())->shouldBeCalled()->will(function () use (&$moved, $storedUrl): string {
            $moved = true;

            return $storedUrl;
        });

        return $file->reveal();
    }
}
