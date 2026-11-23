<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

/**
 * A link to a person's or an organization's page on an outside tool (LinkedIn, Malt, a directory). Its source and
 * its identifier recognize it: the identifier names the person or the organization on that tool, in one form,
 * however the link was pasted. The pasted address opens it. For a tool that Prospecter does not know, the source is
 * the address's host, and the identifier is the address in one form.
 */
final readonly class ExternalReference
{
    private const string WEB_ADDRESS = '~^(?:https?://)?[\p{L}\p{N}-]+(?:\.[\p{L}\p{N}-]+)+(?::\d+)?(?:[/?#]\S*)?$~iu';
    private const string SCHEME = '~^https?://~i';
    private const string WITHOUT_SCHEME = 'https://';
    private const string SCHEME_AND_WWW = '~^(?:https?://)?(?:www\.)?~';
    private const string QUERY_FRAGMENT_AND_FINAL_SLASH = '~(?:[?#].*)?$~';
    private const string HOST = '~^[^/]+~';

    private function __construct(public string $source, public string $identifier, public string $address)
    {
    }

    /**
     * @throws NotAWebAddress|ReferenceOfAnotherKind|UnrecognizedPage
     */
    public static function designating(string $address, Designated $designated): self
    {
        $pasted = trim($address);

        if (1 !== preg_match(self::WEB_ADDRESS, $pasted)) {
            throw NotAWebAddress::of($address);
        }
        $opened = 1 === preg_match(self::SCHEME, $pasted) ? $pasted : self::WITHOUT_SCHEME.$pasted;
        $bare = (string) preg_replace(self::SCHEME_AND_WWW, '', mb_strtolower($pasted));

        foreach (KnownSource::cases() as $source) {
            $identifier = $source->identifierIn($bare);

            if (null !== $identifier) {
                return self::ofAKnownSource($source, $identifier, $opened, $designated);
            }
        }

        return self::ofAnUnknownSource($bare, $opened);
    }

    /**
     * A reference as Prospecter recognized it when it was saved, whatever today's sources say about its address.
     */
    public static function restored(string $source, string $identifier, string $address): self
    {
        return new self($source, $identifier, $address);
    }

    /**
     * The reference that its address gives today: a source that became known takes over its host's links. When
     * today's rules refuse the address, the reference stays as it is, so that a saved reference keeps its value.
     */
    public function recognizedAgain(Designated $designated): self
    {
        try {
            return self::designating($this->address, $designated);
        } catch (NotAWebAddress|ReferenceOfAnotherKind|UnrecognizedPage) {
            return $this;
        }
    }

    /**
     * The reference's source and identifier, which recognize it whatever its address.
     */
    public function key(): string
    {
        return sprintf('%s#%s', $this->source, $this->identifier);
    }

    /**
     * @throws ReferenceOfAnotherKind
     */
    private static function ofAKnownSource(KnownSource $source, string $identifier, string $address, Designated $designated): self
    {
        if ($source->designates() !== $designated) {
            throw ReferenceOfAnotherKind::from($source);
        }

        return new self($source->value, $identifier, $address);
    }

    /**
     * @throws UnrecognizedPage
     */
    private static function ofAnUnknownSource(string $bare, string $address): self
    {
        $identifier = rtrim((string) preg_replace(self::QUERY_FRAGMENT_AND_FINAL_SLASH, '', $bare), '/');
        $host = 1 === preg_match(self::HOST, $identifier, $match) ? $match[0] : $identifier;

        if (array_any(KnownSource::cases(), static fn (KnownSource $source): bool => $source->hosts($bare))) {
            throw UnrecognizedPage::on($host);
        }

        return new self($host, $identifier, $address);
    }
}
