<?php

namespace App\Service\Image;

class UrlImageInfo
{
    /**
     * @var CipherHandler
     */
    private $cipherHandler;
    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $parentName;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
    private $extension;

    public function __construct(string $url, CipherHandler $cipherHandler)
    {
        if (!preg_match('/^(?<protocol>http|https):\/\/(?<host>[[:alnum:]\.]+)\/(?<path>.+)\/(?<part1>[[:alnum:]]+)(&(?<operation>[[:alnum:]-_\=]+))?\.(?<ext>[[:alnum:]]+)$/', $url, $matches)) {
            throw new NotFoundException(sprintf('Url for static operation "%" is malformed', $url));
        }

        $this->protocol = $matches['protocol'];
        $this->host = $matches['host'];
        $this->path = $matches['path'];
        $this->parentName = $matches['part1'];
        $this->operation = empty($matches['operation']) ? null : $cipherHandler->decode($matches['operation']);
        $this->extension = $matches['ext'];
        $this->cipherHandler = $cipherHandler;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getParentName(): string
    {
        return $this->parentName;
    }

    /**
     * @return UrlImageInfo
     */
    public function getParent(): self
    {
        $parent = clone $this;
        $parent->setOperation(null);

        return $parent;
    }

    public function getTargetName(): string
    {
        $fileName = $this->hasOperation() ? sprintf('%s&%s', $this->parentName, $this->cipherHandler->encode($this->operation)) : $this->parentName;

        return sprintf('%s.%s', $fileName, $this->extension);
    }

    public function getTargetUrl(): string
    {
        return sprintf(
            '%s://%s/%s/%s',
            $this->protocol,
            $this->host,
            $this->path,
            $this->getTargetName()
        );
    }

    public function getParentUrl(): string
    {
        return $this->getParent()->getTargetUrl();
    }

    public function setOperation(?string $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getOperationName(): ?string
    {
        return explode(':', $this->operation)[0];
    }

    /**
     * @return array
     */
    public function getOperationArguments(): ?array
    {
        return array_map('intval', explode(',', explode(':', $this->operation)[1]));
    }

    public function hasOperation(): bool
    {
        return isset($this->operation);
    }
}
