<?php

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
use Prospecter\Core\Prospecting\Application\AttachFile\AttachFile;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\HttpFoundation\File\File;

/**
 * `POST /api/exchanges/{id}/attachments`, for an AI (ADR 0163): the file given by its address on the web, which the
 * API downloads and sends to the private bucket with the form of an upload, in place of the browser (ADR 0172).
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
     * @param string $id   the id of the exchange
     * @param string $url  the address on the web of the file, served to anyone (no sign-in), 10 MB at most, downloaded by Prospecter without following a redirect
     * @param string $name the name the file is joined under, 255 characters at most
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'attach-file', description: 'Join to an exchange a file by its address on the web, which Prospecter downloads and keeps private; a file behind a sign-in (an e-mail, a private drive) cannot be downloaded. Needs the right to manage the personal data. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true))]
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
