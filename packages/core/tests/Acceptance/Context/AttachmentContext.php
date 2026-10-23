<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\AttachFile\AttachFile;
use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\AttachmentDownloadResponse;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachment;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\RemoveAttachment\RemoveAttachment;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Tests\Core\Acceptance\Expect;

/**
 * The files joined to "cet échange": the client sends each to the storage, then joins the upload to the exchange.
 * A file is named in the scenarios by its name, its contents made of it.
 */
final class AttachmentContext implements Context
{
    use SharesTheScenario;
    private const int MEGABYTE = 1024 * 1024;
    private const string MEDIA_TYPE = 'application/octet-stream';

    /** @var array<string, string> by name */
    private array $contents = [];
    /** @var array<string, string> the attachment by name */
    private array $attachments = [];
    /** @var array<string, string> the upload, then the kept file, by name */
    private array $files = [];
    private ?AttachmentDownloadResponse $download = null;

    #[When('/^je joins "(?<file>[^"]+)"(?: et "(?<other>[^"]+)")? à cet échange$/')]
    public function iAttach(string $file, string $other = ''): void
    {
        foreach (array_filter([$file, $other]) as $name) {
            $this->attach($name, sprintf('Le contenu de %s', $name));
        }
    }

    #[When('je joins un fichier :file de :megabytes Mo à cet échange')]
    public function iAttachAFileOf(string $file, int $megabytes): void
    {
        $this->attach($file, str_repeat('x', $megabytes * self::MEGABYTE));
    }

    #[Given(':file est joint à cet échange')]
    public function aFileIsAttached(string $file): void
    {
        $this->iAttach($file);
        $this->expectAccepted();
    }

    #[Given(':count fichiers sont joints à cet échange')]
    public function filesAreAttached(int $count): void
    {
        foreach (range(1, $count) as $number) {
            $this->aFileIsAttached(sprintf('fichier-%d.pdf', $number));
        }
    }

    #[When('je joins encore le même envoi à cet échange')]
    public function iAttachTheSameUploadAgain(): void
    {
        $this->application->dispatch(new AttachFile($this->application->signedIn(), $this->scenario->theExchange(), $this->application->newIdentity(), (string) end($this->files), 'encore.pdf', $this->application->now()));
    }

    #[When('je note un e-mail reçu de :contact avec :file joint')]
    public function iNoteAnEmailWith(string $contact, string $file): void
    {
        $this->scenario->rememberTheExchange($this->application->newIdentity());
        $this->application->dispatch(new NoteExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->scenario->personIdOf($contact), Channel::Email->value, Direction::Inbound->value, $this->application->now(), null, false, $this->application->now()));
        $this->expectAccepted();
        $this->aFileIsAttached($file);
    }

    #[When('je retire :file')]
    public function iRemove(string $file): void
    {
        $this->application->dispatch(new RemoveAttachment($this->application->signedIn(), $this->scenario->theExchange(), $this->attachments[$file], $this->application->now()));
    }

    #[When('je télécharge :file')]
    public function iDownload(string $file): void
    {
        $this->download = $this->application->downloadAttachment(new DownloadAttachment($this->application->signedIn(), $this->scenario->theExchange(), $this->attachments[$file]));
    }

    #[Then('le détail de l\'échange liste :')]
    public function theDetailLists(TableNode $table): void
    {
        $detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange()));
        Expect::that(null !== $detail, sprintf('The exchange was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($table->getColumn(0), array_map(static fn (AttachmentResponse $attachment): string => $attachment->name, $detail->attachments));
    }

    #[Then('le détail de l\'échange cache :count pièces jointes')]
    public function theDetailHidesTheAttachments(int $count): void
    {
        $detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange()));
        Expect::that(null !== $detail, sprintf('The exchange was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same([[], $count], [$detail->attachments, $detail->hiddenAttachments]);
    }

    #[Then(':file est à rendre conforme')]
    public function theFileIsToMakeConform(string $file): void
    {
        $detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange()));
        $attachment = array_find($detail->attachments ?? [], static fn (AttachmentResponse $attachment): bool => $attachment->name === $file);
        Expect::that(true === $attachment?->toMakeConform, sprintf('%s was expected to be made conform', $file));
    }

    #[Then('je télécharge :file tel que je l\'ai joint')]
    public function iDownloadItAsAttached(string $file): void
    {
        $this->iDownload($file);
        Expect::that(null !== $this->download, sprintf('A link was expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same($file, $this->download->name);
        Expect::same($this->contents[$file], $this->application->privateStorage->read($this->download->url));
    }

    #[Then('le fichier :file n\'est plus téléchargeable')]
    public function theFileIsNoLongerDownloadable(string $file): void
    {
        $this->iDownload($file);
        Expect::that(null === $this->download, 'No link was expected');
        Expect::that(!$this->application->privateStorage->holds($this->files[$file]), sprintf('%s was expected to be removed from the storage', $file));
    }

    #[Then('la timeline de :contact montre que l\'échange a :count pièces jointes')]
    public function theTimelineCountsTheAttachments(string $contact, int $count): void
    {
        $timeline = $this->application->consultTimeline(new ConsultTimeline($this->application->signedIn(), $this->scenario->personIdOf($contact)));
        Expect::same([$count], array_map(static fn ($exchange): int => $exchange->attachmentCount, $timeline->exchanges ?? []));
    }

    private function attach(string $name, string $contents): void
    {
        $this->contents[$name] = $contents;
        $this->files[$name] = $this->application->privateStorage->send($contents, self::MEDIA_TYPE);
        $this->attachments[$name] = $this->application->newIdentity();
        $this->application->dispatch(new AttachFile($this->application->signedIn(), $this->scenario->theExchange(), $this->attachments[$name], $this->files[$name], $name, $this->application->now()));
    }

    private function expectAccepted(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('It was expected to be accepted, got: %s', $this->application->refusal()?->getMessage()));
    }
}
