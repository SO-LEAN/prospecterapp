<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What a member records about an exchange that took place: its facts and its outcome, done or missed. When known, it
 * also holds where the message was read (ADR 0166) or the imported e-mail it came from (ADR 0154).
 */
final readonly class Noting
{
    private function __construct(
        public ExchangeFacts $facts,
        public ExchangeStatus $outcome,
        private MessageReference $reference,
        public ?ImportedEmail $importedFrom = null,
    ) {
    }

    /**
     * The member logs it by hand, or an AI logs it for them.
     */
    public static function of(ExchangeFacts $facts, ExchangeStatus $outcome, ?MessageReference $reference = null): self
    {
        return new self($facts, $outcome, $reference ?? MessageReference::none());
    }

    /**
     * The import of the member's mailbox logs it as the member would. It is done.
     */
    public static function imported(ExchangeFacts $facts, ImportedEmail $email): self
    {
        return new self($facts, ExchangeStatus::Done, MessageReference::none(), $email);
    }

    /**
     * @throws ChannelNotMissed
     */
    public function ensureMissable(): void
    {
        $this->facts->ensureMissable($this->outcome);
    }

    /**
     * Fingerprints an imported e-mail by its identity, and any other exchange by where it was read (ADR 0166).
     */
    public function fingerprintWith(PersonId|OrganizationId $correspondent): ExchangeFingerprint
    {
        if (null !== $this->importedFrom) {
            return ExchangeFingerprint::ofEmail($this->importedFrom->identity, $correspondent);
        }

        return $this->reference->fingerprintOf($this->facts, $correspondent);
    }
}
