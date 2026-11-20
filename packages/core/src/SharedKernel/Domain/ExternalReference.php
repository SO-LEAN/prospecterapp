<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAWebAddress;
use Prospecter\Core\SharedKernel\Domain\Exception\ReferenceOfAnotherKind;
use Prospecter\Core\SharedKernel\Domain\Exception\UnrecognizedPage;

/**
 * A link to a page of a person or an organization on a tool outside (LinkedIn, Malt, a directory...): its source and
 * the identifier of what it designates there, in one form however it was pasted, which recognize it; and the address
 * pasted, which opens it. A tool Prospecter does not know is the host of the address, the address in one form its
 * identifier.
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
        $bare = preg_replace(self::SCHEME_AND_WWW, '', mb_strtolower($pasted));

        foreach (KnownSource::cases() as $source) {
            $identifier = $source->identifierIn($bare);

            if (null !== $identifier) {
                return self::ofAKnownSource($source, $identifier, $opened, $designated);
            }
        }

        return self::ofAnUnknownSource($bare, $opened);
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
        $identifier = rtrim(preg_replace(self::QUERY_FRAGMENT_AND_FINAL_SLASH, '', $bare), '/');
        preg_match(self::HOST, $identifier, $host);

        if (array_any(KnownSource::cases(), static fn (KnownSource $source): bool => $source->hosts($bare))) {
            throw UnrecognizedPage::on($host[0]);
        }

        return new self($host[0], $identifier, $address);
    }
}
