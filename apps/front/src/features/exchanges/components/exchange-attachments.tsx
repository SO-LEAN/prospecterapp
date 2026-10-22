import { Paperclip, X } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { env } from '@/shared/config/env'
import { formatSize } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { FileDropZone } from '@/shared/ui/file-drop-zone'
import { downloadAttachment } from '../attachments'
import { useAttachFiles, useRemoveAttachment } from '../mutations'
import type { Attachment, ExchangeDetail } from '../queries'

/**
 * The files joined to an exchange, each downloaded or removed, and the zone joining more while the exchange has
 * room for them.
 */
export function ExchangeAttachments({ exchange }: { exchange: ExchangeDetail }) {
  const { t } = useTranslation()
  const attach = useAttachFiles()

  if (exchange.attachments.length === 0 && !exchange._links.attachFile) {
    return null
  }

  return (
    <section className="space-y-2" aria-labelledby="attachments">
      <h3 id="attachments" className="font-medium">
        {t('exchanges.attachments.title')}
      </h3>
      {exchange.attachments.length > 0 && (
        <ul className="space-y-1">
          {exchange.attachments.map((attachment) => (
            <AttachmentLine key={attachment.id} exchangeId={exchange.id} attachment={attachment} />
          ))}
        </ul>
      )}
      {exchange._links.attachFile && (
        <FileDropZone
          invitation={attach.isPending ? t('exchanges.attachments.sending') : t('exchanges.attachments.drop')}
          choose={t('exchanges.attachments.choose')}
          hint={t('exchanges.attachments.heaviest')}
          disabled={attach.isPending}
          onFiles={(files) => attach.mutate({ exchangeId: exchange.id, files })}
        />
      )}
    </section>
  )
}

/**
 * Its link is the address of the API, which reads nothing without the access token: a click asks it a link signed
 * for a minute, and the browser downloads the file from there.
 */
function AttachmentLine({ exchangeId, attachment }: { exchangeId: string; attachment: Attachment }) {
  const { t } = useTranslation()
  const remove = useRemoveAttachment()
  const removeLink = attachment._links.removeAttachment

  return (
    <li className="flex items-center gap-2">
      <Paperclip aria-hidden className="size-4 shrink-0 text-muted-foreground" />
      <a
        href={`${env.apiUrl}${attachment._links.download.href}`}
        aria-label={t('exchanges.attachments.download', { name: attachment.name })}
        className="min-w-0 flex-1 truncate text-primary underline-offset-4 hover:underline"
        onClick={(event) => {
          event.preventDefault()
          void downloadAttachment(exchangeId, attachment.id)
        }}
      >
        {attachment.name}
      </a>
      <span className="shrink-0 text-muted-foreground text-xs">{formatSize(attachment.size)}</span>
      {removeLink && (
        <Button
          variant="ghost"
          size="icon"
          aria-label={t('exchanges.attachments.remove', { name: attachment.name })}
          disabled={remove.isPending}
          onClick={() => remove.mutate({ params: { path: { id: exchangeId, attachmentId: attachment.id } } })}
        >
          <X aria-hidden />
        </Button>
      )}
    </li>
  )
}
