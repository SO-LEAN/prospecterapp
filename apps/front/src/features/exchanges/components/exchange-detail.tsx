import { useQuery } from '@tanstack/react-query'
import { Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/shared/ui/sheet'
import { Skeleton } from '@/shared/ui/skeleton'
import { useCorrectExchange, useCorrectExchangeText, useEraseExchange } from '../mutations'
import { type Author, type ExchangeDetail, exchangeQuery } from '../queries'
import { correctionOf } from '../schema'
import { ExchangeAttachments } from './exchange-attachments'
import { ExchangeForm } from './exchange-form'
import { ExchangeSummary } from './exchange-line'
import { ExchangeText } from './exchange-text'
import { TextCorrectionForm } from './text-correction-form'

type Props = { exchangeId: string | null; onClose: () => void }

/** The whole of an exchange beside the card it was opened from, which stays where it was. */
export function ExchangeDetailSheet({ exchangeId, onClose }: Props) {
  const { t } = useTranslation()

  return (
    <Sheet open={exchangeId !== null} onOpenChange={(open) => !open && onClose()}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-md">
        <SheetHeader>
          <SheetTitle>{t('exchanges.detail.title')}</SheetTitle>
          <SheetDescription className="sr-only">{t('exchanges.detail.description')}</SheetDescription>
        </SheetHeader>
        {exchangeId && <ExchangePanel exchangeId={exchangeId} onErased={onClose} />}
      </SheetContent>
    </Sheet>
  )
}

function ExchangePanel({ exchangeId, onErased }: { exchangeId: string; onErased: () => void }) {
  const exchange = useQuery(exchangeQuery(exchangeId))

  if (!exchange.data) {
    return (
      <div className="space-y-3 px-4">
        <Skeleton className="h-8 w-2/3" />
        <Skeleton className="h-20 w-full" />
      </div>
    )
  }

  return <Exchange exchange={exchange.data} onErased={onErased} />
}

/** What is being corrected: all its facts, its text only, or nothing while it is read. */
type Correcting = 'facts' | 'text' | null

function Exchange({ exchange, onErased }: { exchange: ExchangeDetail; onErased: () => void }) {
  const { t } = useTranslation()
  const [correcting, setCorrecting] = useState<Correcting>(null)
  const correct = useCorrectExchange()
  const correctText = useCorrectExchangeText()
  const authorOf = (author: Author) => nameOf(author.name) || author.email || t('exchanges.detail.aMember')
  const params = { path: { id: exchange.id } }
  const mayDeclareConform = Boolean(exchange._links.makeConform)

  if (correcting === 'facts') {
    return (
      <div className="px-4 pb-4">
        <ExchangeForm
          defaultValues={correctionOf(exchange)}
          mayDeclareConform={mayDeclareConform}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correct.mutateAsync({ params, body: { madeConform: false, ...body } })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  if (correcting === 'text') {
    return (
      <div className="px-4 pb-4">
        <TextCorrectionForm
          text={exchange.text}
          mayDeclareConform={mayDeclareConform}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correctText.mutateAsync({ params, body })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  return (
    <div className="space-y-4 px-4 pb-4 text-sm">
      <ExchangeSummary exchange={exchange} interlocutor={exchange.interlocutor}>
        {exchange.text && (
          <div className="mt-2">
            <ExchangeText
              text={exchange.text}
              flagged={exchange.textFlagged}
              toMakeConform={exchange.textToMakeConform}
              className="whitespace-pre-wrap break-words"
            />
          </div>
        )}
      </ExchangeSummary>
      <div className="space-y-1 text-muted-foreground text-xs">
        <p>
          {t('exchanges.detail.noted', {
            moment: formatMoment(exchange.noted.at),
            author: authorOf(exchange.noted.by),
          })}
        </p>
        {exchange.corrected && (
          <p>
            {t('exchanges.detail.corrected', {
              moment: formatMoment(exchange.corrected.at),
              author: authorOf(exchange.corrected.by),
            })}
          </p>
        )}
        {exchange.madeConform && (
          <p>
            {t('exchanges.detail.madeConform', {
              moment: formatMoment(exchange.madeConform.at),
              author: authorOf(exchange.madeConform.by),
            })}
          </p>
        )}
      </div>
      <ExchangeAttachments exchange={exchange} />
      <div className="flex flex-wrap gap-2">
        {exchange._links.correctExchange ? (
          <Button variant="outline" onClick={() => setCorrecting('facts')}>
            <Pencil aria-hidden /> {t('exchanges.detail.correct')}
          </Button>
        ) : (
          exchange._links.correctText && (
            <Button variant="outline" onClick={() => setCorrecting('text')}>
              <Pencil aria-hidden /> {t('exchanges.detail.correctText')}
            </Button>
          )
        )}
        {exchange._links.eraseExchange && <EraseExchange exchangeId={exchange.id} onErased={onErased} />}
      </div>
    </div>
  )
}

const nameOf = ({ firstName, lastName }: { firstName?: string | null; lastName?: string | null }) =>
  [firstName, lastName].filter(Boolean).join(' ')

/** Erasing an exchange cannot be undone: it is confirmed first. */
function EraseExchange({ exchangeId, onErased }: { exchangeId: string; onErased: () => void }) {
  const { t } = useTranslation()
  const erase = useEraseExchange()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await erase.mutateAsync({ params: { path: { id: exchangeId } } })
    setOpen(false)
    onErased()
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <Trash2 aria-hidden /> {t('exchanges.detail.erase')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('exchanges.detail.eraseTitle')}</DialogTitle>
          <DialogDescription>{t('exchanges.detail.eraseDescription')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={erase.isPending} onClick={() => void confirm()}>
            {t('exchanges.detail.eraseConfirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
