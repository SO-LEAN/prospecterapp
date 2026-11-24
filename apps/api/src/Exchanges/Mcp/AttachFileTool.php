<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\AttachmentInput;
use App\Security\User;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\FilesOnTheWeb;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\WebAddressInput;
use App\Shared\UploadForms;
use App\Shared\UploadSender;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\Attachment\AttachFile\AttachFile;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Symfony\Component\HttpFoundation\File\File;

/**
 * `POST /api/exchanges/{id}/attachments`, for an AI (ADR 0163). The AI gives the file's web address. The API
 * downloads the file and sends it to the private bucket with an upload form, in the browser's place (ADR 0172).
 */
#[Adapts('api_exchanges_attach_file')]
final readonly class AttachFileTool
{
    public function __construct(
        private ExchangeWrites $writes,
        private ToolInputs $inputs,
        private UploadForms $uploadForms,
        private FilesOnTheWeb $files,
        private UploadSender $uploads,
        private Identities $identities,
    ) {
    }

    /**
     * @param string $id   the exchange's id
     * @param string $url  the file's web address. It must serve the file to anyone, without a sign-in, and the file must be 10 MB at most. Prospecter downloads it without following redirects
     * @param string $name the name to attach the file under, 255 characters at most
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'attach-file', description: 'Attach a file to an exchange by its web address. Prospecter downloads the file and keeps it private. It cannot download a file behind a sign-in, such as an e-mail or a private drive. Needs the right to manage personal data. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true))]
    public function __invoke(string $id, string $url, string $name): array
    {
        $this->writes->writable($id, User::PERSONAL_DATA);
        $form = $this->uploadForms->prepareUpload();

        return $this->files->whileDownloaded($this->inputs->valid(new WebAddressInput($url)), $form->heaviest, function (File $file) use ($id, $form, $name): array {
            $this->uploads->upload($form, $file);

            return $this->writes->write(
                $id,
                new AttachmentInput($form->upload, $name),
                fn (AttachmentInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): AttachFile => new AttachFile(prospectorId: $memberId, exchangeId: $exchange->id, id: $this->identities->next(), upload: $input->upload, name: $input->name, executedAt: $now),
                User::PERSONAL_DATA,
            );
        });
    }
}
